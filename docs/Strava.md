# Strava Integration

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

- Run
- VirtualRun

### Walk

- Walk

### Bike

- EBikeRide
- MountainBikeRide
- EMountainBikeRide
- GravelRide
- Handcycle
- Ride
- VirtualRide

### Swim

- Swim

### Other

- Elliptical
- Hike
- StairStepper
- Snowshoe

### Daily Steps

- Strava functions primarily as an activity tracking platform, not a daily steps counter. The RTE Ruby app does not handle or store daily step data, which aligns with its focus on tracking discrete activities rather than continuous step counting.

Any Strava activity type not listed above is not being stored or tracked in the system.

---

## Integration Process

### **1. Authentication**

The application uses OAuth2 for authentication with Strava's API:

```ruby
@client = Strava::OAuth::Client.new(
  client_id: Rails.application.credentials[Rails.env.to_sym][:strava_client_id],
  client_secret: Rails.application.credentials[Rails.env.to_sym][:strava_client_secret],
)
```

The authentication flow consists of:
1. Redirecting the user to Strava's authorization page
2. Receiving a callback with an authorization code
3. Exchanging the authorization code for an access token and refresh token
4. Storing the tokens in the `DataSourceProfile` model

### **2. Token Refresh Process**

Strava access tokens expire after a few hours. The application refreshes tokens automatically:

```ruby
def refresh_access_token!
  token_information = @client.oauth_token(
    refresh_token: @dsp.refresh_token,
    grant_type: "refresh_token"
  )

  @dsp.update(access_token: token_information.access_token,
              token_expires_at: token_information.expires_at,
              refresh_token: token_information.refresh_token)

  token_information
end
```

### **3. Connection Process**

When a user connects their Strava account:

1. The user initiates the connection from the profile settings page
2. The application redirects to Strava's authorization page with required scopes
3. After authorization, Strava redirects back to the application with an authorization code
4. The application exchanges the code for access and refresh tokens
5. The tokens and user information are stored in the `DataSourceProfile` model

```ruby
def connect_strava_data_source
  redirect_to "https://www.strava.com/oauth/authorize?client_id=#{Rails.application.credentials[Rails.env.to_sym][:strava_client_id]}&response_type=code&redirect_uri=#{users_profiles_strava_oauth_callback_url}&approval_prompt=force&scope=activity:read_all"
end
```

### **4. Data Fetching Process**

Data is fetched from Strava in three ways:

#### 4.1 Webhook-Triggered Fetching

When Strava sends a webhook notification:
1. The notification is received by the application
2. The webhook payload is validated
3. If it's an activity creation event, the activity is fetched and processed
4. The data is stored in the database

```ruby
def processWebhook(array $data): array
{
    Log::debug('StravaService: Processing webhook', ['data' => $data]);

    // Verify this is an activity creation event
    if (! isset($data['object_type']) || $data['object_type'] !== 'activity' ||
        ! isset($data['aspect_type']) || $data['aspect_type'] !== 'create') {
        return ['status' => 'ignored', 'reason' => 'Not an activity creation event'];
    }

    // Find the user by owner_id
    $sourceProfile = DataSourceProfile::where('access_token_secret', $data['owner_id'])
        ->whereHas('source', function ($query) {
            return $query->where('short_name', 'strava');
        })
        ->first();

    // Refresh token if needed
    if (Carbon::parse($sourceProfile->token_expires_at)->lt(Carbon::now())) {
        $this->refreshToken($sourceProfile->refresh_token);
        $sourceProfile->refresh();
    }

    // Set access token for API calls
    $this->accessToken = $sourceProfile->access_token;

    // Fetch the activity data
    try {
        $activity = $this->fetchActivity($data['object_id']);
        $processedActivity = $this->processActivity($activity, $sourceProfile);
        
        return [
            'status' => 'success',
            'activity' => $processedActivity,
            'user' => $sourceProfile->user,
            'sourceProfile' => $sourceProfile,
        ];
    } catch (Exception $e) {
        Log::error('StravaService: Failed to fetch activity', [
            'message' => $e->getMessage(),
            'activity_id' => $data['object_id'],
        ]);

        return ['status' => 'error', 'reason' => $e->getMessage()];
    }
}
```

#### 4.2 Manual Synchronization

Users can manually trigger data synchronization:
1. The user selects a date range to synchronize
2. A `ManualStravaDataFetcher` instance is created
3. The fetcher requests activities for the date range
4. The data is processed and stored

```ruby
def sync_data
  logger.info "Starting to synchronize Strava data for user: #{ @current_user.email } participating in #{ @current_event.name } starting on: #{ @start_date }, ending on: #{ @end_date }"

  @current_user.open_events.each do |subscribed_event|
    next unless device_synced_for_event(subscribed_event)

    data_created_or_updated_run = false
    data_created_or_updated_walk = false
    data_created_or_updated_bike = false
    data_created_or_updated_swim = false
    data_created_or_updated_other = false

    if subscribed_event.permits_modality_ex?(:run, @current_user) && !@run_activities_simplified.blank?
      data_created_or_updated_run = store_simplified_daily_data(user: @current_user, data_source: @data_source, event: subscribed_event, modality: :run, simplified_activities: @run_activities_simplified)
    end

    # Similar code for other modalities...

    data_created_or_updated = data_created_or_updated_run || data_created_or_updated_walk || data_created_or_updated_bike || data_created_or_updated_swim || data_created_or_updated_other
    if data_created_or_updated
      update_user_team_points_summary_and_celebrations(user: @current_user, event: subscribed_event) if data_created_or_updated
    end
  end

  logger.info "Done synchronizing Strava data for user: #{ @current_user.email } participating in #{ @current_event.name } starting on: #{ @start_date }, ending on: #{ @end_date }"
  true
end
```

#### 4.3 Periodic Synchronization

The application periodically synchronizes data for all users:
1. A scheduled job runs at regular intervals
2. For each user with a Strava connection, a data fetcher is created
3. The fetcher requests recent activities
4. The data is processed and stored

### **5. Webhook Management**

The application manages Strava webhooks for real-time updates:

#### 5.1 Creating a Subscription

```ruby
def createSubscription(): array
{
    try {
        // Use the correct webhook route from settings.php
        $callbackUrl = route('profile.device-sync.webhook', ['sourceSlug' => 'strava'], true);

        // Log the URL we're using
        Log::debug('StravaService: Creating subscription', [
            'callback_url' => $callbackUrl,
            'client_id' => $this->clientId,
        ]);

        if (empty($callbackUrl)) {
            Log::error('StravaService: No callback URL available');
            return ['error' => 'Callback URL is not configured. Please set Strava Subscription URL first.'];
        }

        $verifyToken = config('services.strava.webhook_verification_code');

        $response = Http::post('https://www.strava.com/api/v3/push_subscriptions', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'callback_url' => $callbackUrl,
            'verify_token' => $verifyToken,
        ]);

        if ($response->successful()) {
            Log::debug('StravaService: Subscription created successfully', [
                'response' => $response->json(),
            ]);

            return $response->json();
        }
    } catch (Exception $e) {
        Log::error('StravaService: Failed to create subscription', [
            'message' => $e->getMessage(),
        ]);

        return ['error' => $e->getMessage()];
    }
}
```

#### 5.2 Verifying a Subscription

```ruby
def verifyWebhook($code): int
{
    return http_response_code(204);
}
```

### **6. Data Structure Received**

The Strava API returns activity data with this structure:

```json
{
  "id": 1234567890,
  "type": "Run",
  "sport_type": "Run",
  "start_date": "2020-01-01T12:00:00Z",
  "start_date_local": "2020-01-01T07:00:00Z",
  "distance": 10000,
  "moving_time": 3600,
  "elapsed_time": 3600,
  "total_elevation_gain": 100,
  "average_speed": 2.78,
  "max_speed": 5.5,
  "has_heartrate": true,
  "average_heartrate": 150,
  "max_heartrate": 180
}
```

### **7. Data Processing Flow**

The data processing flow consists of:

1. **Fetching Activities**: Activities are fetched from Strava's API
2. **Categorizing Activities**: Activities are categorized into modalities (run, walk, bike, swim, other)
3. **Converting Units**: Distance is converted from meters to miles
4. **Simplifying Data**: Data is simplified into a standardized format
5. **Storing Data**: The simplified data is stored in the database

```ruby
@activities.each do |activity|
  logger.debug "StravaDataFetcher#store_data: #{ activity.type }"

  @run_activities[activity.start_date_local] += [activity] if %w[Run VirtualRun].include?(activity.type)
  @walk_activities[activity.start_date_local] += [activity] if activity.type == "Walk"
  @bike_activities[activity.start_date_local] += [activity] if %w[EBikeRide MountainBikeRide EMountainBikeRide GravelRide Handcycle Ride VirtualRide].include?(activity.type)
  @swim_activities[activity.start_date_local] += [activity] if activity.type == "Swim"
  @other_activities[activity.start_date_local] += [activity] if %w[Elliptical Hike StairStepper Snowshoe].include? activity.type
end

simplify_all_modalities
```

### **8. Data Storage**

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

### **9. Disconnection Process**

When a user disconnects their Strava account:

1. The user initiates disconnection from the profile settings page
2. The application revokes the access token with Strava
3. The `DataSourceProfile` record is deleted
4. Optionally, all data from the Strava data source can be deleted

```ruby
def disconnect_strava_data_source
  strava_data_source = DataSource.find_by(short_name: "strava")
  dsp = DataSourceProfile.find_by(user: current_user, data_source: strava_data_source)
  
  begin
    # Revoke token with Strava
    uri = URI.parse("https://www.strava.com/oauth/deauthorize")
    request = Net::HTTP::Post.new(uri)
    request.set_form_data(
      "access_token" => dsp.access_token
    )
    
    req_options = {
      use_ssl: uri.scheme == "https",
    }
    
    response = Net::HTTP.start(uri.hostname, uri.port, req_options) do |http|
      http.request(request)
    end
  ensure
    dsp.destroy! unless dsp.blank?
    
    if params[:delete_data] == "yes"
      current_user.open_events.each do |event|
        data_to_delete = UserPoint.where(user: current_user, event: event, data_source: strava_data_source)
        data_to_delete.delete_all
      end
    end
  end
end
```

### **10. OAuth Token Refresh**

The application handles OAuth token refresh for Strava:

```ruby
class StravaOauthRefresh
  include Rte::RailsLoggerInterface

  REQUIRED_KEYS = [:access_token, :refresh_token, :expires_at]

  def initialize(dsp)
    @dsp = dsp
  end

  def fix
    return if @dsp.refresh_token.present?

    @response = Faraday.post("https://www.strava.com/oauth/token") do |req|
      req.params["client_id"] = Rails.application.credentials[Rails.env.to_sym][:strava_client_id]
      req.params["client_secret"] = Rails.application.credentials[Rails.env.to_sym][:strava_client_secret]
      req.params["grant_type"] = "refresh_token"
      req.params["refresh_token"] = @dsp.access_token
    end

    process_response
  end

  def process_response
    reply = JSON.parse(@response.body, symbolize_names: true)

    if reply.has_key? :errors
      logger.error "StravaOauthRefresh#process_response: Unable to convert refresh token for user #{@dsp.user.email}. Error: #{reply[:errors]}"
      return
    end

    unless (reply.keys & REQUIRED_KEYS).count == 3
      logger.error "StravaOauthRefresh#process_response: Unable to convert refresh token for user #{@dsp.user.email}. One of the required keys is missing: #{REQUIRED_KEYS}"
      return
    end

    @dsp.update(access_token: reply[:access_token],
                token_expires_at: Time.at(reply[:expires_at]),
                refresh_token: reply[:refresh_token]
    )
  end
end
```

### **11. Error Handling**

The application includes robust error handling:

1. Network errors are retried through ActiveJob's retry mechanism
2. API response errors are logged and handled gracefully
3. Authentication errors are reported to the user
4. Token refresh failures are logged and handled

### **12. Modality Mapping**

The application maps Strava activity types to standardized modalities:

```php
public function modality(string $modality): string
{
    return match ($modality) {
        'Run', 'VirtualRun' => 'run',
        'Walk' => 'walk',
        'EBikeRide', 'MountainBikeRide', 'EMountainBikeRide', 'GravelRide', 'Handcycle', 'Ride', 'VirtualRide' => 'bike',
        'Swim' => 'swim',
        'Elliptical', 'Hike', 'StairStepper', 'Snowshoe' => 'other',
        default => 'daily_steps', // Consistent with other services
    };
}
```

### **13. Legacy Token Conversion**

The application includes a rake task to convert legacy Strava tokens to the new OAuth2 format:

```ruby
namespace :rte do
  # rails rte:strava_oauth_fix
  desc "Convert Strava OAuth forever refresh tokens to expirable ones."
  task strava_oauth_fix: :environment do
    strava_ds = DataSource.find_by(short_name: "strava")
    all_strava_dsps = DataSourceProfile.where(data_source: strava_ds, refresh_token: nil)
    puts "CONVERTING #{ all_strava_dsps.count } STRAVA PROFILES WITHOUT OAUTH REFRESH TOKEN"
    progressbar = ProgressBar.create(title: "Strava Profiles", starting_at: 0, total: all_strava_dsps.count , format: "%a %e %P% Processed: %c from %C")
    all_strava_dsps.each do |dsp|
      StravaOauthRefresh.new(dsp).fix
      progressbar.increment
    end
    puts "DONE."
  end
end
```

---

## Important Links

### Strava API Brand Guidelines

[https://developers.strava.com/guidelines/](https://developers.strava.com/guidelines/)

### Strava API Documentation

[https://developers.strava.com/docs/reference/](https://developers.strava.com/docs/reference/)

### Strava Webhook Documentation

[https://developers.strava.com/docs/webhooks/](https://developers.strava.com/docs/webhooks/)

### Strava OAuth Documentation

[https://developers.strava.com/docs/authentication/](https://developers.strava.com/docs/authentication/)

## Developer Resources

### Strava Developer Portal

[https://www.strava.com/settings/api](https://www.strava.com/settings/api)

### Strava API Explorer

[https://developers.strava.com/playground/](https://developers.strava.com/playground/)

### Rate Limits

Strava API has the following rate limits:
- 100 requests per 15 minutes
- 1000 requests per day

### Authentication Scopes

The application requires the following scopes:
- `activity:read_all`: Read all activities from the user's Strava account
- `profile:read_all`: Read the user's profile information
