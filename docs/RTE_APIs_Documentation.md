# RTE APIS

# 🚀 Get started here

This collection guides you through CRUD operations (GET, POST, PUT, DELETE), variables, and tests.

## 🔖 **How to use this collection**

#### **Step 1: Send requests**

RESTful APIs allow you to perform CRUD operations using the POST, GET, PUT, and DELETE HTTP methods.

This collection contains each of these request types. Open each request and click "Send" to see what happens.

#### **Step 2: View responses**

Observe the response tab for status code (200 OK), response time, and size.

#### **Step 3: Send new Body data**

Update or add new data in "Body" in the POST request. Typically, Body data is also used in PUT and PATCH requests.

```
{
    "name": "Add your name in the body"
}

 ```

#### **Step 4: Update the variable**

Variables enable you to store and reuse values in Postman. We have created a variable called `base_url` with the sample request [https://postman-api-learner.glitch.me](https://postman-api-learner.glitch.me). Replace it with your API endpoint to customize this collection.

#### **Step 5: Add tests in the "Tests" tab**

Tests help you confirm that your API is working as expected. You can write test scripts in JavaScript and view the output in the "Test Results" tab.

<img src="https://content.pstmn.io/b5f280a7-4b09-48ec-857f-0a7ed99d7ef8/U2NyZWVuc2hvdCAyMDIzLTAzLTI3IGF0IDkuNDcuMjggUE0ucG5n">

## 💪 Pro tips

- Use folders to group related requests and organize the collection.
- Add more scripts in "Tests" to verify if the API works as expected and execute flows.
    

## ℹ️ Resources

[Building requests](https://learning.postman.com/docs/sending-requests/requests/)  
[Authorizing requests](https://learning.postman.com/docs/sending-requests/authorization/)  
[Using variables](https://learning.postman.com/docs/sending-requests/variables/)  
[Managing environments](https://learning.postman.com/docs/sending-requests/managing-environments/)  
[Writing scripts](https://learning.postman.com/docs/writing-scripts/intro-to-scripts/)

## API Endpoints

## Auth

### Login

- **Method:** `POST`
- **URL:** `{{base_url}}/login`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "email": "{{email}}",
    "password":"{{password}}"
}
  ```

### Forgot Password

- **Method:** `POST`
- **URL:** `{{base_url}}/forgot-password`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "email":"manoj.bisht2592@gmail.com"
}
  ```

### Reset Password

- **Method:** `POST`
- **URL:** `{{base_url}}/reset-password`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "email":"abc@test.com",
    "otp":"1771",
    "password":"11111111",
    "password_confirmation":"11111111"
}
  ```

### Update Password

- **Method:** `POST`
- **URL:** `{{base_url}}/user/update-password`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "current_password": "",
    "password":"",
    "password_confirmation": ""
}
  ```

## User Points

### List

- **Method:** `GET`
- **URL:** `{{base_url}}/user/points`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "start_date":"2025-04-01",
    "end_date":"2025-04-28",
   // "modality":"other",
    "event_id":59
}
  ```

### Create

- **Method:** `POST`
- **URL:** `{{base_url}}/user/points`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "points": [
        {"modality": "run", "data_source_id": 1,"amount": "129"}
    ],
    "date": "2025-04-01",
    "event_id": 2,
    "transaction_id": null,
    "note":"TEST NOTE"
}
  ```

### Update

- **Method:** `PATCH`
- **URL:** `{{base_url}}/user/points`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "points": [
        {"point_id":678884,"amount": "129"},
        {"point_id":678885,"amount": "129"}
    ],
    "event_id": 2,
    "note":"TEST NOTE"
}
  ```

### List/Calendar

- **Method:** `GET`
- **URL:** `{{base_url}}/user/points/listing`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "event_id":59,
    "mode":"calendar", //Supported values are list, calendar
    "start_date":"2025-04-01", //Required
    "end_date":"2025-04-30", //Required
    "page_limit":100 //Optional Default 100
}
  ```

### Find One

- **Method:** `GET`
- **URL:** `{{base_url}}/user/points/view`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "event_id":2,
    "date":"2025-01-01"
}
  ```

### Sync Points

- **Method:** `POST`
- **URL:** `{{base_url}}/user/event/sync-points`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "event_id": 2,
    "sync_start_date":"2025-04-01",
    "data_source": "garmin" //Supported values are fitbit,garmin,strava 
}
  ```

### Monthlies

- **Method:** `GET`
- **URL:** `{{base_url}}/user/stats`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "event_id": 64
}
  ```

### Total Points

- **Method:** `GET`
- **URL:** `{{base_url}}/user/stats`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "event_id": 64
}
  ```

### Last 30 days

- **Method:** `GET`
- **URL:** `{{base_url}}/user/points/last-30-days`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
```json
{
    "event_id": 64
}
```

### By Modality

- **Method:** `GET`
- **URL:** `{{base_url}}/user/points/by-modality`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
```json
{
    "event_id": 64
}
```

## Teams

### List

- **Method:** `GET`
- **URL:** `{{base_url}}/teams`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
```json
{
    "event_id":2,
    "list_type":"all", //all, own, joined, other
    "page":1, //Page offset (increse/decrease current_page to fetch prev/next records)
    "page_limit":20, //Use page limit (if not used default page limit is 100),
    "term":""
}
```

### Achievements

- **Method:** `GET`
- **URL:** `{{base_url}}/teams/achievements`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
```json
{
    "event_id": 2,
    "team_id": 45309
}
```

### InviteMembership

- **Method:** `POST`
- **URL:** `{{base_url}}/teams/invite/membership`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "emails":["p1@g.com"],
    "event_id":2,
    "team_id": 45309
}
  ```

### Create Team

- **Method:** `POST`
- **URL:** `{{base_url}}/teams/create`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "event_id":2,
    "name": "Team TEST P1 P2",
    "public_profile":true, //Boolean (true/false)
    "settings":{} //JSON format
}
  ```

### Update Team

- **Method:** `PATCH`
- **URL:** `{{base_url}}/teams/update/900`
- **Headers:**
  - `Content-Type`: `application/json`
- **Body:**
  ```json
  /*
If want to update only name or public visibility use request body as below
- Update only team name - pass only name key { "name": "Test Team"}
- Update only public visibility - pass only public_profile key {"public_profile":true}
*/
{
    "name": "Test Team",
    "public_profile":true //Boolean (true/false)
}
  ```

### Join Team Request

- **Method:** `POST`
- **URL:** `{{base_url}}/teams/join-request`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
   "team_id":45335,
   "event_id":2
}
  ```

### Leave Team

- **Method:** `POST`
- **URL:** `{{base_url}}/teams/leave`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "team_id":45309
}
  ```

### Cancel Join Team Request

- **Method:** `POST`
- **URL:** `{{base_url}}/teams/cancel-request`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
   "team_id":45060,
   "event_id":2
}
  ```

### Accept Membership Request

- **Method:** `POST`
- **URL:** `{{base_url}}/teams/membership-request/accept`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "user_id": 165219,
    "team_id": 45060,
    "event_id":2
}
  ```

### Decline Membership Request

- **Method:** `POST`
- **URL:** `{{base_url}}/teams/membership-request/decline`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "user_id": 165219,
    "team_id": 45060,
    "event_id":2
}
  ```

### Membership Requests

- **Method:** `GET`
- **URL:** `{{base_url}}/teams/membership/requests`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "team_id":232
}
  ```

### Membership Invites

- **Method:** `GET`
- **URL:** `{{base_url}}/teams/membership/invites`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "team_id":45226
}
  ```

### Dissolve Team

- **Method:** `POST`
- **URL:** `{{base_url}}/teams/dissolve`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
     "team_id":45082
}
  ```

### Transfer Admin Role

- **Method:** `POST`
- **URL:** `{{base_url}}/teams/transfer-admin-role`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "team_id":45309,
    "member_id":165669
}
  ```

### Find

- **Method:** `GET`
- **URL:** `{{base_url}}/teams/team/1`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "event_id":2
}
  ```

### Invitation Search Users

- **Method:** `GET`
- **URL:** `{{base_url}}/teams/invitation/users/search`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "event_id": 2,
    "email": "0414mimi@gmail.com",
    "page": 1,
    "page_limit": 10
}
  ```

### Remove Team Member

- **Method:** `POST`
- **URL:** `{{base_url}}/teams/member/remove`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "team_id":34,
    "event_id":2,
    "user_id":3
}
  ```

### Accept Team Follow Request

- **Method:** `POST`
- **URL:** `{{base_url}}/teams/follow/request/accept`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "team_id":45336,
    "user_id":165669,
    "event_id":2
}
  ```

### Decline Team Follow Request

- **Method:** `POST`
- **URL:** `{{base_url}}/teams/follow/request/decline`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "team_id":45336,
    "user_id":165669,
    "event_id":2
}
  ```

### Team Followers

- **Method:** `GET`
- **URL:** `{{base_url}}/teams/followers`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "team_id":45335
}
  ```

### Total Points

- **Method:** `GET`
- **URL:** `{{base_url}}/teams/points/total`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "team_id": 45098,
    "event_id": 55
}
  ```

### Monthlies Points

- **Method:** `GET`
- **URL:** `{{base_url}}/teams/points/monthlies`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "team_id": 45211,
    "event_id": 64
    // "start_date": "2024-01-01", // optional
    // "end_date": "2024-12-31"    // optional
}
  ```

### Teams Follow to List

- **Method:** `GET`
- **URL:** `{{base_url}}/teams/follow-to/list`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "event_id":64,
    "term":""
}
  ```

## User Achievements (Deprecated)

### List

- **Method:** `GET`
- **URL:** `{{base_url}}/user/achievements`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "event_id":2,
    "year":"2024"
}
  ```

## User Note

### Update

- **Method:** `PATCH`
- **URL:** `{{base_url}}/user/note`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "note": "Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes."
}
  ```

### Find

- **Method:** `GET`
- **URL:** `{{base_url}}/user/note`
- **Headers:**
  - `Accept`: `application/json`

## Events

### Find

- **Method:** `GET`
- **URL:** `{{base_url}}/events/event/64`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "dates":"2024-08-03" //Optional
}
  ```

### Find Event Goals

- **Method:** `GET`
- **URL:** `{{base_url}}/event/goals`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "event_id":2
}
  ```

### Achievements

- **Method:** `GET`
- **URL:** `{{base_url}}/achievements`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "event_id":64, //Required
    "action":"team", //Required params user or team
    "team_id":45226 // Required if action value is team
}
  ```

### LIst

- **Method:** `GET`
- **URL:** `{{base_url}}/events`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "page":1,
    "page_limit":20,
    "list_type":"all" //Default all | supported values all/active/expired
}
  ```

### Import Miles

- **Method:** `POST`
- **URL:** `{{base_url}}/event/miles/import`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "event_id":64,
    "manual_entry" : [
        {"year":2016, "miles": 500},
         {"year":2017, "miles": 500}
    ]
}
  ```

### Missing Mile Years

- **Method:** `GET`
- **URL:** `{{base_url}}/event/missing-miles/years`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "event_id":64
}
  ```

### Modalities

- **Method:** `GET`
- **URL:** `{{base_url}}/modalities`
- **Headers:**
  - `Accept`: `application/json`

### Event Modalities

- **Method:** `GET`
- **URL:** `{{base_url}}/event/modalities`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "event_id": 64
}
  ```

### Amerithon Distances

- **Method:** `GET`
- **URL:** `{{base_url}}/event/amerithon-distances`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "distance":51.312
}
  ```

## User Profile

### FindFullProfile

- **Method:** `GET`
- **URL:** `{{base_url}}/user/profile/complete`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "start_date":"2024-10-01", //Optional (Default will get all listing)
    "end_date":" 2025-10-30", //Optional (Default will get all listing)
    "modality":"other", //Optional (Default will get all modality results)
    "event_id":64, //Optional (Default will user preferred event_id)
    "year":"2024", //Optional (Default will be current year)
    "user_id":"", //Optional (Default will be current logged in user)
    "lifetimePoint":false //Optional (Default false)
}
  ```

### Update

- **Method:** `PATCH`
- **URL:** `{{base_url}}/user/profile`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "email": "contact@w3creatives.com",
    "first_name": "Puneet",
    "last_name": "Gupta",
    "display_name": "Puneet Gupta",
    "birthday": null,
    "bio": null,
    "time_zone": "Mountain Time (US & Canada)",
    "street_address1": null,
    "street_address2": null,
    "city": null,
    "state": null,
    "country": null,
    "zipcode": null,
    "gender": "unknown",
    "settings": "{\"rty_goals\": [{\"run-the-year-2024\": 2024}, {\"2025-miles-in-2025\": 2025}], \"denied_notifications\": [], \"manual_entry_populates_all_events\": false}",
    "shirt_size": "unknown_size",
    "preferred_event_id": 2
}
  ```

### FindBasicProfile

- **Method:** `GET`
- **URL:** `{{base_url}}/user/profile/basic`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "user_id":"" //Optional (Will fetch current logged in user)
}
  ```

### Update Notifications

- **Method:** `POST`
- **URL:** `{{base_url}}/user/notifications/update`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "name":"bibs", //Required | supported values are bibs,follow_requests,team_bibs,team_follow_requests,team_updates
    "notification_enabled":true
}
  ```

### Update Manual Entry

- **Method:** `POST`
- **URL:** `{{base_url}}/user/manual-entry/update`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "manual_entry":false
}
  ```

### Update Tracker Attitude

- **Method:** `POST`
- **URL:** `{{base_url}}/user/tracker-attitude/update`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "attitude":"default" //Required | Supported values default,yoda,tough_love,positive,cheerleader,scifi,historian,superhero
}
  ```

### Update RTY Mileage Goals

- **Method:** `POST`
- **URL:** `{{base_url}}/user/rty-mileage-goal/update`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "event_id":64,
    "mileage_goal":1000
}
  ```

### User Settings

- **Method:** `GET`
- **URL:** `{{base_url}}/user/setting`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "setting":"rty_mileage_goal", //Required | Supported values notification,manual_entry,attitude,rty_mileage_goal,modalities
    "event_id":64 //Required if setting=rty_mileage_goal or modalities
}
  ```

### Event Participations

- **Method:** `GET`
- **URL:** `{{base_url}}/user/events/participants`
- **Headers:**
  - `Accept`: `application/json`

### Update Event Privacy

- **Method:** `POST`
- **URL:** `{{base_url}}/user/event/privacy`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "event_id": 64,
    "public_profile":false
}
  ```

### Update Event Modality

- **Method:** `POST`
- **URL:** `{{base_url}}/user/event/modality`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "event_id":64,
    "name": "other", //daily_steps,run,walk,swim,bike,other
    "notification_enabled":true
}
  ```

### Team Follow Request

- **Method:** `POST`
- **URL:** `{{base_url}}/user/follow/team/request`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "team_id":45155,
    "event_id":64
}
  ```

### Team Following

- **Method:** `GET`
- **URL:** `{{base_url}}/user/teams/following`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "event_id":64
}
  ```

### Team Following Requests

- **Method:** `GET`
- **URL:** `{{base_url}}/user/teams/following/requests`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "event_id":2
}
  ```

### Undo Team Follow Request

- **Method:** `POST`
- **URL:** `{{base_url}}/user/follow/team/request/undo`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "team_id":45155,
    "event_id":64
}
  ```

### Team Invitations

- **Method:** `GET`
- **URL:** `{{base_url}}/user/team/membership/invites`
- **Body:**
  ```json
  {
    "event_id":64
}
  ```

### Accept Team Invitation

- **Method:** `POST`
- **URL:** `{{base_url}}/user/team/membership-request/accept`
- **Body:**
  ```json
  {
    "team_id":3,
    "event_id":3
}
  ```

### Decline Team Invitation

- **Method:** `POST`
- **URL:** `{{base_url}}/user/team/membership-request/decline`
- **Body:**
  ```json
  {
    "team_id":3,
    "event_id":3
}
  ```

## Data Sources

### List

- **Method:** `GET`
- **URL:** `{{base_url}}/source/profiles`
- **Headers:**
  - `Accept`: `application/json`

### Create

- **Method:** `POST`
- **URL:** `{{base_url}}/user/source/profiles/create`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "data_source_id":3,
    "access_token":"eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiIyMkQzTVMiLCJzdWIiOiI2UFdSUlkiLCJpc3MiOiJGaXR",
    "refresh_token":"eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiIyMkQzTVMiLCJzdWIiOiI2UFdSUlkiLC",
    "token_expires_at":"2024-06-13 08:02:07",
    "access_token_secret":"4343"
}
  ```

### Delete

- **Method:** `DELETE`
- **URL:** `{{base_url}}/user/source/profiles/delete`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "data_source_id":3,
    "synced_mile_action":"preserve" //preserve, delete
}
  ```

## Schedule Quest

### Activities

- **Method:** `GET`
- **URL:** `{{base_url}}/quests/activities`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "event_id":59
}
  ```

### Create

- **Method:** `POST`
- **URL:** `{{base_url}}/quests/create`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "event_id":59, //Required
    "activity_id":573, //Required
    "date":"2025-04-25", //Required
    "invitees_email":["thecodersahil@gmail.com"] //Optional
}
  ```

## Manage Quest

### List

- **Method:** `GET`
- **URL:** `{{base_url}}/quests/quests`
- **Headers:**
  - `Accept`: `application/json`
  - `Content-Type`: `application/json`
- **Body:**
  ```json
  /*
is_archived=true will list all quests which have archived=true or date < (current_date - 14 days)
*/
{
    "event_id":59,
    "page_limit":10,
    "is_archived":false, //Default false | send is_archived = true for Quest History List,
    "list_type":"all" //Optional | Default all | supported values all, upcoming (only applicable when is_archived=false)
}
  ```

### Update

- **Method:** `POST`
- **URL:** `{{base_url}}/quests/update`
- **Headers:**
  - `Accept`: `application/json`

### Delete

- **Method:** `DELETE`
- **URL:** `{{base_url}}/quests/delete`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "quest_id":223076
}
  ```

### Move to History

- **Method:** `POST`
- **URL:** `{{base_url}}/quests/archive`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "quest_id":223080
}
  ```

### Find

- **Method:** `GET`
- **URL:** `{{base_url}}/quest/223080`
- **Headers:**
  - `Accept`: `application/json`

## My Journal

### List

- **Method:** `GET`
- **URL:** `{{base_url}}/quests/journal`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "event_id":64,
    "page_limit":10
}
  ```

## Configurations

### Update Event Template

- **Method:** `POST`
- **URL:** `{{base_url}}/event/template/update`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "event_id": 2,
    "template":1
}
  ```

## User Follows

### User Participations

- **Method:** `GET`
- **URL:** `{{base_url}}/follow/user-participations`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "event_id":2,
    "page_limit":100,
    "term":"P2"
}
  ```

### Followers

- **Method:** `GET`
- **URL:** `{{base_url}}/follow/followers`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "event_id": 2
}
  ```

### Followings

- **Method:** `GET`
- **URL:** `{{base_url}}/follow/followings`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "event_id": 2
}
  ```

### Undo Following

- **Method:** `POST`
- **URL:** `{{base_url}}/follow/undo-following`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "event_id": 64,
    "user_id":2
}
  ```

### Accept Follow Request

- **Method:** `POST`
- **URL:** `{{base_url}}/follow/follow-request/accept`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "event_id": 2,
    "user_id":165219
}
  ```

### Decline Follow Request

- **Method:** `POST`
- **URL:** `{{base_url}}/follow/follow-request/decline`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "event_id": 2,
    "user_id":165219
}
  ```

### Request Following

- **Method:** `POST`
- **URL:** `{{base_url}}/follow/following/request`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "event_id": 2,
    "user_id":165670
}
  ```

### Cancel Following Request

- **Method:** `POST`
- **URL:** `{{base_url}}/follow/follow-request/cancel`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "event_id": 2,
    "user_id":165670
}
  ```

### Following Requests

- **Method:** `GET`
- **URL:** `{{base_url}}/follow/following/requests`
- **Headers:**
  - `Accept`: `application/json`
- **Body:**
  ```json
  {
    "event_id":2
}
  ```

## Assets

### Flag URL

- **Method:** `GET`
- **URL:** `{{base_url}}/flag-banner`
- **Headers:**
  - `Accept`: `application/json`

## Tutorials

### Event Tutorial

- **Method:** `GET`
- **URL:** `{{base_url}}/event/tutorials`
- **Body:**
  ```json
  {
    "event_id": 64
}
  ```
