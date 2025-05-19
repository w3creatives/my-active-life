# Garmin Integration

## Activity Types

The following activity types are stored in the database:

## Main Modalities

- run
- walk
- bike
- swim
- other
- daily_steps

---

### Run

- RUNNING
- TRACK_RUNNING
- STREET_RUNNING
- TREADMILL_RUNNING
- TRAIL_RUNNING
- VIRTUAL_RUN
- INDOOR_RUNNING
- OBSTACLE_RUN
- OBSTACLE_RUNNING
- ULTRA_RUN
- ULTRA_RUNNING

### Walk

- WALKING
- CASUAL_WALKING
- SPEED_WALKING
- GENERIC

### Bike

- CYCLING
- CYCLOCROSS
- DOWNHILL_BIKING
- INDOOR_CYCLING
- MOUNTAIN_BIKING
- RECUMBENT_CYCLING
- ROAD_BIKING
- TRACK_CYCLING
- VIRTUAL_RIDE

### Swim

- SWIMMING
- LAP_SWIMMING
- OPEN_WATER_SWIMMING

### Other

- HIKING
- CROSS_COUNTRY_SKIING
- MOUNTAINEERING
- ELLIPTICAL
- STAIR_CLIMBING

### Daily Steps

Activities that count toward Walk metrics are also counted as daily steps when tracked through the daily steps endpoint:

- WALKING
- CASUAL_WALKING
- SPEED_WALKING
- GENERIC

Daily steps are adjusted by subtracting walk and run activities to avoid double-counting, as seen in the `adjust_max_daily_steps` method.

```ruby
# we cannot guarantee the ordering when daily steps arrive wrt activities. we wait for 2.5min for the
# walk and run activities to arrive.
# the daily_activity includes activities (walk, run) *and* daily steps
# so, we back out sum of activities from daily steps to obtain daily steps
def adjust_max_daily_steps(date, max_daily_steps, subscribed_event)
  sleep(150)

  adjustment = @current_user.user_points.where(date: date, event: subscribed_event, modality: ["walk", "run", "bike"]).sum(:amount)
  if (max_daily_steps[:distance_in_miles] - adjustment) >= 0
    logger.debug "#{ @current_user.email }: GarminDataFetcher#adjust_max_daily_steps: Removing #{ adjustment } points for #{ date } and #{subscribed_event.name}"
    max_daily_steps[:distance_in_miles] -= adjustment
  else
    logger.debug "#{ @current_user.email }: GarminDataFetcher#adjust_max_daily_steps: Nothing to subtract for #{ date } and #{subscribed_event.name}"
  end

  max_daily_steps
end
```

---

## Integration Process

### **1. Authentication**

The application uses OAuth1 for authentication with Garmin's API:

```ruby
@consumer = OAuth::Consumer.new(
  Rails.application.credentials[Rails.env.to_sym][:garmin_consumer_key],
  Rails.application.credentials[Rails.env.to_sym][:garmin_consumer_secret],
  site: Rails.application.credentials[Rails.env.to_sym][:garmin_health_api_url],
  debug_output: false
)
```

The authentication flow consists of:
1. Creating a request token
2. Redirecting the user to Garmin's authorization page
3. Receiving a callback with an authorized request token
4. Exchanging the request token for an access token
5. Storing the access token and secret in the `DataSourceProfile` model

### **2. API Endpoints Used**

The application interacts with Garmin's Wellness API through several endpoints:

- **Daily Steps**: `https://apis.garmin.com/wellness-api/rest/dailies`
- **Activities**: `https://apis.garmin.com/wellness-api/rest/activities`
- **Backfill Dailies**: `https://apis.garmin.com/wellness-api/rest/backfill/dailies`
- **Backfill Activities**: `https://apis.garmin.com/wellness-api/rest/backfill/activities`
- **User Registration**: `https://apis.garmin.com/wellness-api/rest/user/registration`

### **3. Connection Process**

When a user connects their Garmin device:

1. The user initiates the connection from the profile settings page
2. The application creates an OAuth request token
3. The user is redirected to Garmin's authorization page
4. After authorization, Garmin redirects back to the application
5. The application exchanges the request token for an access token
6. The access token and secret are stored in the `DataSourceProfile` model

```ruby
def connect_garmin_data_source
  # Create OAuth consumer
  consumer = OAuth::Consumer.new(
    Rails.application.credentials[Rails.env.to_sym][:garmin_consumer_key],
    Rails.application.credentials[Rails.env.to_sym][:garmin_consumer_secret],
    site: Rails.application.credentials[Rails.env.to_sym][:garmin_health_api_url],
    request_token_path: "/oauth-service/oauth/request_token",
    authorize_path: "/oauth-service/oauth/authorize",
    access_token_path: "/oauth-service/oauth/access_token"
  )
  
  # Get request token
  request_token = consumer.get_request_token(
    oauth_callback: users_profiles_garmin_oauth_callback_url
  )
  
  # Store request token in session
  session[:request_token] = request_token.token
  session[:request_token_secret] = request_token.secret
  
  # Redirect to Garmin authorization page
  redirect_to request_token.authorize_url
end
```

### **4. Data Fetching Process**

Data is fetched from Garmin in two ways:

#### 4.1 Webhook-Triggered Fetching

When Garmin sends a webhook notification:
1. The notification is received by the application
2. A `GarminDataFetchJob` is queued
3. The job creates a `GarminDataFetcher` instance
4. The fetcher processes the updates

```ruby
class GarminDataFetchJob < ApplicationJob
  queue_as :default
  
  retry_on Faraday::ConnectionFailed
  retry_on OpenSSL::SSL::SSLError
  retry_on Faraday::SSLError
  
  discard_on ActiveJob::DeserializationError
  
  def perform(options)
    now = Time.now.utc.iso8601
    logger.debug "GarminDataFetchJob::perform: Performing Garmin job at #{ now }"
    
    fetcher = GarminDataFetcher.new(options)
    fetcher.process_updates
  end
end
```

#### 4.2 Manual Synchronization

Users can manually trigger data synchronization:
1. The user selects a date range to synchronize
2. A `ManualGarminDataFetcher` instance is created
3. The fetcher uses the Backfill API to request historical data
4. The data is processed and stored

```ruby
def backfill_data(access_token, start_date, end_date)
  # Determine if any event allows daily steps
  any_event_allows_daily_steps = @current_user.open_events.any? { |e| e.permits_modality_ex?(:other, @current_user) }
  
  start_epoch = start_date.to_i
  end_epoch = end_date.to_i
  
  if any_event_allows_daily_steps
    url = "https://apis.garmin.com/wellness-api/rest/backfill/dailies?summaryStartTimeInSeconds=#{ start_epoch }&summaryEndTimeInSeconds=#{ end_epoch }"
    access_token.get(url)
    sleep(60)
  end
  
  url = "https://apis.garmin.com/wellness-api/rest/backfill/activities?summaryStartTimeInSeconds=#{ start_epoch }&summaryEndTimeInSeconds=#{ end_epoch }"
  access_token.get(url)
end
```

### **5. Data Structure Received**

The Garmin API returns JSON data with this structure:

```json
{
  "summaryId": "sd24cc06-5a0dd6d8-6",
  "activityType": "WALKING",
  "activeKilocalories": 86,
  "steps": 1977,
  "distanceInMeters": 75.73,
  "durationInSeconds": 86400,
  "activeTimeInSeconds": 332,
  "startTimeInSeconds": 1510856408,
  "startTimeOffsetInSeconds": -21600,
  "moderateIntensityDurationInSeconds": 4560,
  "vigorousIntensityDurationInSeconds": 2280,
  "floorsClimbed": 3,
  "minHeartRateInBeatsPerMinute": 52,
  "maxHeartRateInBeatsPerMinute": 80,
  "averageHeartRateInBeatsPerMinute": 52,
  "restingHeartRateInBeatsPerMinute": 52
}
```

### **6. Data Processing Flow**

The data processing flow consists of:

1. **Parsing API Response**: The JSON response is parsed into Ruby objects
2. **Categorizing Activities**: Activities are categorized into modalities (run, walk, bike, swim, other)
3. **Converting Units**: Distance is converted from meters to miles
4. **Simplifying Data**: Data is simplified into a standardized format
5. **Storing Data**: The simplified data is stored in the database

```ruby
def parse_api_response(callback_url, response)
  activity_timestamp = nil
  distance_in_meters = 0

  @daily_steps = Hash.new { [] }
  @run_activities = Hash.new { [] }
  @walk_activities = Hash.new { [] }
  @bike_activities = Hash.new { [] }
  @swim_activities = Hash.new { [] }
  @other_activities = Hash.new { [] }

  # Parse JSON response
  json_response = JSON.parse(response.body)
  
  # Process activities
  json_response.each do |activity|
    # Extract timestamp
    activity_timestamp = Time.at(activity["startTimeInSeconds"]).utc
    activity_date = activity_timestamp.to_date
    
    # Extract distance
    distance_in_meters = activity["distanceInMeters"] || 0
    distance_in_miles = (distance_in_meters / 1609.344).set_precision(3)
    
    # Categorize by activity type
    case activity["activityType"]
    when *RUN_ACTIVITIES
      @run_activities[activity_date] += [{ distance_in_miles: distance_in_miles, transaction_id: activity["summaryId"] }]
    when *WALK_ACTIVITIES
      @walk_activities[activity_date] += [{ distance_in_miles: distance_in_miles, transaction_id: activity["summaryId"] }]
    # ... other categories
    end
  end
end
```

### **7. Data Storage**

Data is stored in the `UserPoint` model with these attributes:

```ruby
UserPoint.create!(
  user: user,
  data_source: data_source,
  event: event,
  date: date,
  amount: distance,
  modality: modality,
  transaction_id: transaction_id
)
```

### **8. Disconnection Process**

When a user disconnects their Garmin device:

1. The user initiates disconnection from the profile settings page
2. The application creates an OAuth access token using the stored credentials
3. The application sends a DELETE request to Garmin's user registration endpoint
4. The `DataSourceProfile` record is deleted
5. Optionally, all data from the Garmin data source can be deleted

```ruby
def disconnect_garmin_data_source
  garmin_data_source = DataSource.find_by(short_name: "garmin")
  dsp = DataSourceProfile.find_by(user: current_user, data_source: garmin_data_source)
  
  begin
    consumer = OAuth::Consumer.new(
      Rails.application.credentials[Rails.env.to_sym][:garmin_consumer_key],
      Rails.application.credentials[Rails.env.to_sym][:garmin_consumer_secret],
      site: Rails.application.credentials[Rails.env.to_sym][:garmin_health_api_url],
      debug_output: false
    )
    
    access_token = OAuth::AccessToken.new(consumer, dsp.access_token, dsp.access_token_secret)
    request_result = access_token.delete("https://apis.garmin.com/wellness-api/rest/user/registration")
  ensure
    dsp.destroy! unless dsp.blank?
    
    if params[:delete_data] == "yes"
      current_user.open_events.each do |event|
        data_to_delete = UserPoint.where(user: current_user, event: event, data_source: garmin_data_source)
        data_to_delete.delete_all
      end
    end
  end
end
```

### **9. Daily Steps Handling**

The application has special handling for daily steps:

1. Daily steps are fetched from the dailies endpoint
2. The system waits 2.5 minutes for all activities to arrive
3. Daily steps are adjusted by subtracting walk and run activities to avoid double-counting
4. The adjusted daily steps are stored in the database

```ruby
def adjust_max_daily_steps(date, max_daily_steps, subscribed_event)
  sleep(150)
  
  adjustment = @current_user.user_points.where(
    date: date, 
    event: subscribed_event, 
    modality: ["walk", "run", "bike"]
  ).sum(:amount)
  
  if (max_daily_steps[:distance_in_miles] - adjustment) >= 0
    max_daily_steps[:distance_in_miles] -= adjustment
  end
  
  max_daily_steps
end
```

### **10. User Interface**

The application provides a user interface for:

1. Connecting a Garmin device
2. Toggling daily steps inclusion
3. Disconnecting a Garmin device
4. Viewing synchronized data

The UI includes informational modals explaining the implications of including daily steps:

```
If your Garmin is NOT GPS-enabled and you would like miles from your daily steps to be added to the tracker, slide the switch for daily steps ON.

If your Garmin IS GPS-enabled all activities will be auto-synced to the tracker. Sliding the switch for daily steps ON will double your miles.

You can always slide the switch for daily steps OFF without having to resync your device.
```

### **11. Error Handling**

The application includes robust error handling:

1. Network errors are retried through ActiveJob's retry mechanism
2. API response errors are logged and handled gracefully
3. Authentication errors are reported to the user
4. Rate limiting is respected with sleep intervals between API calls

---

## Important Links

- **Developer Portal**: Log in to access developer apps: https://developerportal.garmin.com/
- **Garmin Connect**: View your Garmin data: https://connect.garmin.com/
- **API Documentation**: Garmin Health API documentation: https://developer.garmin.com/health-api/overview/

## Tutorials

The application provides video tutorials for users:
- How to sync your Garmin: https://player.vimeo.com/video/278064850
- Updated tutorial: https://player.vimeo.com/video/307769280
