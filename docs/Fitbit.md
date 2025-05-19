# Fitbit Integration

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

Activities categorized as "Run" in Fitbit are mapped to the run modality:
- Run
- Treadmill
- Trail Run
- Indoor Running

### Walk

Activities categorized as "Walk" in Fitbit are mapped to the walk modality:
- Walk
- Hike
- Indoor Walking

### Bike

Activities categorized as "Bike" in Fitbit are mapped to the bike modality:
- Bike
- Indoor Cycling
- Mountain Bike
- Road Bike

### Swim

Activities categorized as "Swim" in Fitbit are mapped to the swim modality:
- Swim
- Open Water Swim
- Pool Swim

### Other

All other activities are mapped to the other modality, including:
- Elliptical
- Stair Climbing
- Yoga
- Pilates
- Weight Training

### Daily Steps

Fitbit provides daily step counts which are tracked separately from activities. Daily steps are converted to distance using the user's stride length or a default conversion factor.

---

## Integration Process

### **1. Authentication**

The application uses OAuth2 for authentication with Fitbit's API:

```ruby
@fitbit_provider = FitbitAPI::Client.new(
  client_id: Rails.application.credentials[Rails.env.to_sym][:fitbit_client_id],
  client_secret: Rails.application.credentials[Rails.env.to_sym][:fitbit_client_secret],
  refresh_token: @data_source_profile.refresh_token
)
```

The authentication flow consists of:
1. Redirecting the user to Fitbit's authorization page
2. Receiving a callback with an authorization code
3. Exchanging the authorization code for an access token and refresh token
4. Storing the tokens in the `DataSourceProfile` model

### **2. Token Refresh Process**

Fitbit access tokens expire after a few hours. The application refreshes tokens automatically:

```ruby
def refresh_access_token!
  client = OAuth2::Client.new(
    Rails.application.credentials[Rails.env.to_sym][:fitbit_client_id],
    Rails.application.credentials[Rails.env.to_sym][:fitbit_client_secret],
    site: "https://api.fitbit.com",
    token_url: "/oauth2/token"
  )

  token = OAuth2::AccessToken.new(
    client,
    @data_source_profile.access_token,
    refresh_token: @data_source_profile.refresh_token
  )

  new_token = token.refresh!
  
  @data_source_profile.update(
    access_token: new_token.token,
    refresh_token: new_token.refresh_token,
    token_expires_at: Time.now + new_token.expires_in
  )
  
  new_token
end
```

### **3. Connection Process**

When a user connects their Fitbit account:

1. The user initiates the connection from the profile settings page
2. The application redirects to Fitbit's authorization page with required scopes
3. After authorization, Fitbit redirects back to the application with an authorization code
4. The application exchanges the code for access and refresh tokens
5. The tokens and user information are stored in the `DataSourceProfile` model
6. A subscription is created to receive real-time updates from Fitbit

```ruby
def connect_fitbit_data_source
  redirect_to "https://www.fitbit.com/oauth2/authorize?response_type=code&client_id=#{Rails.application.credentials[Rails.env.to_sym][:fitbit_client_id]}&redirect_uri=#{users_profiles_fitbit_oauth_callback_url}&scope=activity%20heartrate%20location%20nutrition%20profile%20settings%20sleep%20social%20weight"
end
```

### **4. Subscription Management**

The application creates a subscription with Fitbit to receive real-time updates:

```ruby
def create_new_subscription
  random_sequence = (0...8).map { (65 + rand(26)).chr }.join
  subscription_id = "#{@current_user.id}-#{random_sequence}"

  @fitbit_provider.add_subscription(collection_path: "activities", subscription_id: subscription_id)

  dsp = DataSourceProfile.where(user: @current_user, data_source: @fitbit_data_source)
  dsp.update(access_token_secret: subscription_id)

  @status = ResultStatus.success
end
```

The subscription ID is stored in the `access_token_secret` field of the `DataSourceProfile` model.

### **5. Data Fetching Process**

Data is fetched from Fitbit in two ways:

#### 5.1 Webhook-Triggered Fetching

When Fitbit sends a webhook notification:
1. The notification is received by the application
2. The webhook payload is validated
3. A `FitbitDataFetcher` instance is created
4. The fetcher processes the updates

```ruby
class FitbitDataFetcher
  include Rte::RailsLoggerInterface
  include Rte::Points

  def initialize(params)
    logger.debug "FitbitDataFetcher#new"
    @fitbit_json = params["_json"]
    unless @fitbit_json.present?
      raise(FitbitDataFetcherError, "Expected _json parameter in parameters: params: #{params}.")
    end
    @data_source = DataSource.find_by(short_name: "fitbit")
  end

  def process_updates
    logger.debug "FitbitDataFetcher#process_updates"
    fitbit_json.each do |notification|
      # Fitbit _json subscription may contain multiple users' notifications
      next unless setup_fetch_data_params(notification)
      next unless fetch_data
      store_simplified_data
    end
  end
end
```

#### 5.2 Manual Synchronization

Users can manually trigger data synchronization:
1. The user selects a date range to synchronize
2. A `ManualFitbitDataFetcher` instance is created
3. The fetcher requests activities for the date range
4. The data is processed and stored

```ruby
def sync_data
  logger.info "Starting to synchronize Fitbit data for user: #{@current_user.email} participating in #{@current_event.name} starting on: #{@start_date}, ending on: #{@end_date}"

  # Fetch activity time series data
  fetch_activity_time_series_data
  
  # Fetch tracker activity time series data
  fetch_tracker_activity_time_series_data
  
  # Process the data for each event the user is participating in
  @current_user.open_events.each do |subscribed_event|
    next unless device_synced_for_event(subscribed_event)
    
    # Process data for each modality
    process_event_data(subscribed_event)
  end

  logger.info "Done synchronizing Fitbit data for user: #{@current_user.email} participating in #{@current_event.name} starting on: #{@start_date}, ending on: #{@end_date}"
  true
end
```

### **6. Webhook Format**

Fitbit sends webhook notifications in the following format:

```json
{
  "_json": [
    {
      "collectionType": "activities",
      "date": "2017-11-13",
      "ownerId": "37FDYX",
      "ownerType": "user",
      "subscriptionId": "121606-NKLIBZRP"
    }
  ]
}
```

The application processes these notifications to fetch the actual activity data.

### **7. Data Structure Received**

The Fitbit API returns activity data with this structure:

```json
{
  "activities": [
    {
      "activityId": 17151,
      "activityParentId": 90013,
      "activityParentName": "Walk",
      "calories": 80,
      "description": "Walking less than 2 mph, strolling very slowly",
      "distance": 0.5,
      "duration": 1800000,
      "hasStartTime": true,
      "isFavorite": false,
      "lastModified": "2017-11-13T21:15:18.000Z",
      "logId": 10843898674,
      "name": "Walk",
      "startDate": "2017-11-13",
      "startTime": "09:00",
      "steps": 1077
    }
  ],
  "goals": {
    "activeMinutes": 30,
    "caloriesOut": 3151,
    "distance": 5,
    "floors": 10,
    "steps": 10000
  },
  "summary": {
    "activeScore": -1,
    "activityCalories": 483,
    "caloriesBMR": 1901,
    "caloriesOut": 2262,
    "distances": [
      {
        "activity": "Walk",
        "distance": 0.5
      },
      {
        "activity": "Run",
        "distance": 2
      },
      {
        "activity": "total",
        "distance": 5
      }
    ],
    "fairlyActiveMinutes": 0,
    "lightlyActiveMinutes": 0,
    "marginalCalories": 200,
    "sedentaryMinutes": 1166,
    "steps": 0,
    "veryActiveMinutes": 0
  }
}
```

### **8. Data Processing Flow**

The data processing flow consists of:

1. **Fetching Activities**: Activities are fetched from Fitbit's API
2. **Categorizing Activities**: Activities are categorized into modalities (run, walk, bike, swim, other)
3. **Simplifying Data**: Data is simplified into a standardized format
4. **Storing Data**: The simplified data is stored in the database

```ruby
def store_simplified_data
  logger.debug "FitbitDataFetcher#store_simplified_data: user: #{notification_user.email}, date: #{notification_date}"
  
  # Process data for each event the user is participating in
  notification_user.open_events.each do |subscribed_event|
    next unless device_synced_for_event(subscribed_event)
    
    # Process daily steps
    if subscribed_event.permits_modality_ex?(:daily_steps, notification_user)
      store_user_point(
        event: subscribed_event,
        modality: :other,
        distance: @daily_steps[notification_date.to_s][0][:distance_in_miles],
        date: notification_date,
        transaction_id: notification_date
      )
    end
    
    # Process run activities
    if subscribed_event.permits_modality_ex?(:run, notification_user)
      store_simplified_daily_data(
        user: notification_user,
        data_source: @data_source,
        event: subscribed_event,
        modality: :run,
        simplified_activities: @run_activities_simplified
      )
    end
    
    # Process other modalities similarly
  end
end
```

### **9. Data Storage**

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

### **10. Disconnection Process**

When a user disconnects their Fitbit account:

1. The user initiates disconnection from the profile settings page
2. The application revokes the access token with Fitbit
3. The subscription is deleted
4. The `DataSourceProfile` record is deleted
5. Optionally, all data from the Fitbit data source can be deleted

```ruby
def disconnect_fitbit_data_source
  fitbit_data_source = DataSource.find_by(short_name: "fitbit")
  dsp = DataSourceProfile.find_by(user: current_user, data_source: fitbit_data_source)
  
  begin
    # Revoke token with Fitbit
    uri = URI.parse("https://api.fitbit.com/oauth2/revoke")
    request = Net::HTTP::Post.new(uri)
    request.basic_auth(
      Rails.application.credentials[Rails.env.to_sym][:fitbit_client_id],
      Rails.application.credentials[Rails.env.to_sym][:fitbit_client_secret]
    )
    request.set_form_data(
      "token" => dsp.access_token
    )
    
    req_options = {
      use_ssl: uri.scheme == "https",
    }
    
    response = Net::HTTP.start(uri.hostname, uri.port, req_options) do |http|
      http.request(request)
    end
    
    # Delete subscription
    if dsp.access_token_secret.present?
      subscription_id = dsp.access_token_secret
      
      fitbit_provider = FitbitAPI::Client.new(
        client_id: Rails.application.credentials[Rails.env.to_sym][:fitbit_client_id],
        client_secret: Rails.application.credentials[Rails.env.to_sym][:fitbit_client_secret],
        access_token: dsp.access_token
      )
      
      fitbit_provider.remove_subscription(
        collection_path: "activities",
        subscription_id: subscription_id
      )
    end
  ensure
    dsp.destroy! unless dsp.blank?
    
    if params[:delete_data] == "yes"
      current_user.open_events.each do |event|
        data_to_delete = UserPoint.where(user: current_user, event: event, data_source: fitbit_data_source)
        data_to_delete.delete_all
      end
    end
  end
end
```

### **11. Subscription Verification**

Fitbit requires a verification endpoint for subscriptions:

```ruby
def verify_subscription
  verify_code = params[:verify]
  
  if verify_code == Rails.application.credentials[Rails.env.to_sym][:fitbit_verification_code]
    render json: { success: true }, status: :ok
  else
    render json: { error: "Invalid verification code" }, status: :unauthorized
  end
end
```

### **12. Error Handling**

The application includes robust error handling:

1. Network errors are retried through ActiveJob's retry mechanism
2. API response errors are logged and handled gracefully
3. Authentication errors are reported to the user
4. Token refresh failures are logged and handled

```ruby
def validate_fitbit_client
  unless @data_source_profile.present?
    logger.error "FitbitDataFetcher#validate_fitbit_client: Missing Fitbit Data Source Profile for user #{notification_user.email}."
    return false
  end

  begin
    @fitbit_provider = FitbitAPI::Client.new(
      client_id: Rails.application.credentials[Rails.env.to_sym][:fitbit_client_id],
      client_secret: Rails.application.credentials[Rails.env.to_sym][:fitbit_client_secret],
      refresh_token: @data_source_profile.refresh_token
    )
    
    DataSourceProfile.create_or_update!(
      user: notification_user,
      provider: :fitbit,
      fitbit_provider: fitbit_provider
    )
    
    true
  rescue OAuth2::Error => ex
    logger.error "FitbitDataFetcher#validate_fitbit_client: OAuth2 error for user #{notification_user.email}: #{ex.message}"
    false
  rescue => ex
    logger.error "FitbitDataFetcher#validate_fitbit_client: Unexpected error for user #{notification_user.email}: #{ex.message}"
    false
  end
end
```

### **13. Subscription Management**

The application includes a class to manage Fitbit subscriptions:

```ruby
class FitbitSubscriptionManager
  include Rte::RailsLoggerInterface

  def initialize(current_user)
    @current_user = current_user
    @fitbit_data_source = DataSource.find_by(short_name: "fitbit")
    @data_source_profile = DataSourceProfile.find_by(user: @current_user, data_source: @fitbit_data_source)
    @status = ResultStatus.new
    @existing_subscriptions = []
  end

  def perform
    fetch_existing_subscriptions

    if @status.success?
      if @existing_subscriptions.blank?
        create_new_subscription
      else
        check_existing_subscriptions
      end
    else
      logger.error "FitbitSubscriptionManager::Creator#perform: Couldn't fetch existing subscriptions for #{@current_user.email}: #{@status.error}"
      return false
    end

    true
  end

  def create_new_subscription
    random_sequence = (0...8).map { (65 + rand(26)).chr }.join
    subscription_id = "#{@current_user.id}-#{random_sequence}"

    @fitbit_provider.add_subscription(collection_path: "activities", subscription_id: subscription_id)

    dsp = DataSourceProfile.where(user: @current_user, data_source: @fitbit_data_source)
    dsp.update(access_token_secret: subscription_id)

    @status = ResultStatus.success
  end
end
```

### **14. Batch Subscription Management**

The application includes a rake task to update all Fitbit subscriptions:

```ruby
namespace :rte do
  desc "Save Fitbit users subscription ID"
  task save_fitbit_users_subscription_id: :environment do
    fitbit_ds = DataSource.find_by(short_name: "fitbit")
    fitbit_dsps = DataSourceProfile.where(data_source: fitbit_ds)
    puts "UPDATING #{fitbit_dsps.count} FITBIT PROFILES"
    
    fitbit_dsps.each do |dsp|
      user = User.find(dsp.user_id.to_i)
      subscription_id_date = "Wed, 08 Jan 2020".to_date
      if dsp.created_at.to_date >= subscription_id_date
        puts "DSP created after Jan 8th: #{dsp.created_at}"
        subscription_id = "#{user.id}-rtyfitbitsubscription"
      else
        puts "DSP created before Jan 8th: #{dsp.created_at}"
        subscription_id = "#{user.id}"
      end
      dsp.update(access_token_secret: subscription_id)
      puts "updated dsp ats: #{dsp.access_token_secret}"
    end
  end
end
```

---

## Important Links

### Fitbit API Documentation

[https://dev.fitbit.com/build/reference/web-api/](https://dev.fitbit.com/build/reference/web-api/)

### Fitbit Subscription API

[https://dev.fitbit.com/build/reference/web-api/developer-guide/using-subscriptions/](https://dev.fitbit.com/build/reference/web-api/developer-guide/using-subscriptions/)

### Fitbit OAuth 2.0

[https://dev.fitbit.com/build/reference/web-api/oauth2/](https://dev.fitbit.com/build/reference/web-api/oauth2/)

### Fitbit Developer Portal

[https://dev.fitbit.com/apps](https://dev.fitbit.com/apps)

## Developer Resources

### Rate Limits

Fitbit API has the following rate limits:
- 150 requests per hour
- 150 requests per day for subscription notifications

### Authentication Scopes

The application requires the following scopes:
- `activity`: Access to activity data
- `heartrate
