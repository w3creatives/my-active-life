# Mailboxer Implementation Notes

## Database Tables
Based on the Ruby migrations and schema:

1. `mailboxer_conversations`
   - `id`: Primary key
   - `subject`: String, default empty
   - `created_at`, `updated_at`: Timestamps

2. `mailboxer_notifications`
   - `id`: Primary key
   - `type`: String (for STI)
   - `body`: Text
   - `subject`: String, default empty
   - `sender_type`, `sender_id`: Polymorphic association
   - `conversation_id`: Foreign key to conversations
   - `draft`: Boolean, default false
   - `notification_code`: String
   - `notified_object_type`, `notified_object_id`: Polymorphic association
   - `attachment`: String
   - `global`: Boolean, default false
   - `expires`: Datetime

3. `mailboxer_receipts`
   - `id`: Primary key
   - `receiver_type`, `receiver_id`: Polymorphic association
   - `notification_id`: Foreign key to notifications
   - `is_read`: Boolean, default false
   - `trashed`: Boolean, default false
   - `deleted`: Boolean, default false
   - `mailbox_type`: String (25 chars)
   - `is_delivered`: Boolean, default false
   - `delivery_method`: String
   - `message_id`: String

4. `mailboxer_conversation_opt_outs`
   - `id`: Primary key
   - `unsubscriber_type`, `unsubscriber_id`: Polymorphic association
   - `conversation_id`: Foreign key to conversations

## Database Fields Explained

### `mailboxer_conversations` Table
- **`id`**: Unique identifier for each conversation
- **`subject`**: The subject line of the conversation, defaults to empty string
- **`created_at`**: Timestamp when the conversation was created
- **`updated_at`**: Timestamp when the conversation was last updated

### `mailboxer_notifications` Table
- **`id`**: Unique identifier for each notification
- **`type`**: Class name for Single Table Inheritance (STI), determines the type of notification (e.g., 'Mailboxer::Message')
- **`body`**: The main content/text of the notification or message
- **`subject`**: Subject line for the notification, defaults to empty string
- **`sender_type`**, **`sender_id`**: Polymorphic association identifying who sent the notification
  - `sender_type` typically contains 'User' for user-sent messages
  - `sender_id` contains the ID of the specific sender
- **`conversation_id`**: Foreign key linking to the conversation this notification belongs to
- **`draft`**: Boolean flag indicating if the message is a draft (not yet sent)
- **`notification_code`**: String identifier for categorizing notifications by type or purpose
- **`notified_object_type`**, **`notified_object_id`**: Polymorphic association linking to an object the notification is about
  - For example, a team invitation notification might reference the team
- **`attachment`**: Reference to any file attached to the notification
- **`global`**: Boolean flag indicating if the notification is system-wide (true) or directed (false)
- **`expires`**: Datetime when the notification should expire/be removed

### `mailboxer_receipts` Table
- **`id`**: Unique identifier for each receipt
- **`receiver_type`**, **`receiver_id`**: Polymorphic association identifying who received the notification
  - `receiver_type` typically contains 'User'
  - `receiver_id` contains the ID of the specific recipient
- **`notification_id`**: Foreign key linking to the notification this receipt is for
- **`is_read`**: Boolean indicating if the recipient has read the notification
- **`trashed`**: Boolean indicating if the recipient has moved the notification to trash
- **`deleted`**: Boolean indicating if the recipient has deleted the notification
- **`mailbox_type`**: String indicating which mailbox the notification belongs to (e.g., 'inbox', 'sentbox')
- **`is_delivered`**: Boolean indicating if the notification has been delivered
- **`delivery_method`**: String indicating how the notification was delivered (e.g., 'email', 'database')
- **`message_id`**: String identifier for the message, possibly used for email threading

### `mailboxer_conversation_opt_outs` Table
- **`id`**: Unique identifier for each opt-out record
- **`unsubscriber_type`**, **`unsubscriber_id`**: Polymorphic association identifying who opted out
  - Typically contains 'User' and the user's ID
- **`conversation_id`**: Foreign key linking to the conversation the user opted out from

## Table Relations
- Conversations have many notifications
- Notifications belong to conversations
- Notifications have many receipts
- Receipts belong to notifications
- Senders (polymorphic) have many notifications
- Receivers (polymorphic) have many receipts

## Functionality in Ruby
1. **User Messaging**
   - Users can send messages to other users
   - Users can reply to conversations
   - Messages are organized in conversations

2. **Notification System**
   - System can send automated messages for various events
   - Notifications are tracked with receipts

3. **Message Management**
   - Read/unread status tracking
   - Trash/delete functionality
   - Mailbox types (inbox, sentbox)

## Automated Messages
The system sends automated messages in these scenarios:
1. User follow notifications
   - When a user follows another user
   - When a user unfollows another user
   - When a user requests to follow another user

2. Team-related notifications
   - When a user requests to follow a team
   - When a user leaves a team

3. Achievement notifications
   - New individual achievements
   - New individual streaks
   - Race milestones

4. Account notifications
   - Email change notifications

## Message Types and Data
1. **Sender/Receiver Types**
   - `User`: Most common type for both sender and receiver

2. **Mailbox Types**
   - `inbox`: For received messages
   - `sentbox`: For sent messages

3. **User Request Types** (Enum in PostgreSQL)
   - `request_to_follow_issued`
   - `request_to_follow_approved`
   - `request_to_follow_ignored`

## Email Notifications
The system can send email notifications for messages using the `RteMailer` class. Email notifications are controlled by:
- Configuration in `config/initializers/mailboxer.rb`
- User preferences (`denied_notifications` and `disabled_emails` in user settings)

## Limitations
1. The system appears to be primarily designed for user-to-user and system-to-user messaging
2. Email delivery is optional and configurable
3. Message attachments are supported but implementation details are not clear

## Implementation Notes
1. The Ruby implementation uses the Mailboxer gem
2. The system uses a custom `IMessage` class hierarchy for different message types
3. Messages can be sent programmatically through the `ImSender` service
4. The system integrates with user preferences for notification delivery

## Detailed Column Explanations for `mailboxer_conversations`

### `id`
- **Purpose**: Primary key for uniquely identifying each conversation
- **Values**: Auto-incrementing integer
- **Usage**: Referenced by notifications to group messages in the same thread

### `subject`
- **Purpose**: Stores the topic or title of the conversation
- **Values**: String, defaults to empty string
- **Usage**: Displayed in conversation lists and message headers
- **Behavior**: All messages in the same conversation share this subject

### `created_at`
- **Purpose**: Records when the conversation was first created
- **Values**: Timestamp
- **Usage**: Used for sorting conversations chronologically
- **Behavior**: Automatically set by Rails when record is created

### `updated_at`
- **Purpose**: Records when the conversation was last modified
- **Values**: Timestamp
- **Usage**: Used for sorting conversations by recent activity
- **Behavior**: Automatically updated by Rails when any attribute changes

## Detailed Column Explanations for `mailboxer_notifications`

### `id`
- **Purpose**: Primary key for uniquely identifying each notification
- **Values**: Auto-incrementing integer
- **Usage**: Referenced by receipts to track delivery status

### `type`
- **Purpose**: Identifies the specific class for Single Table Inheritance
- **Values**: String class names like 'Mailboxer::Message', 'Mailboxer::Notification'
- **Usage**: Determines how the notification behaves and is displayed
- **Behavior**: Rails uses this to instantiate the correct class when loading records

### `body`
- **Purpose**: Contains the main content of the message or notification
- **Values**: Text field containing the message content
- **Usage**: Displayed in the message view
- **Behavior**: Can contain formatted text depending on application configuration

### `subject`
- **Purpose**: Subject line for the individual notification
- **Values**: String, defaults to empty string
- **Usage**: May be displayed in notification lists
- **Behavior**: Often inherits from the conversation subject

### `sender_type` and `sender_id`
- **Purpose**: Polymorphic association identifying the sender
- **Values**: 
  - `sender_type`: Class name (typically 'User')
  - `sender_id`: ID of the sender object
- **Usage**: Links to the entity that created the notification
- **Behavior**: Allows messages to be sent by different types of entities

### `conversation_id`
- **Purpose**: Foreign key to the conversation this notification belongs to
- **Values**: Integer referencing a conversation ID
- **Usage**: Groups related messages together
- **Behavior**: All replies in a thread share the same conversation_id

### `draft`
- **Purpose**: Indicates if the message is a draft (not yet sent)
- **Values**: Boolean, defaults to false
- **Usage**: Allows saving messages before sending
- **Behavior**: Draft messages may not generate receipts until finalized

### `notification_code`
- **Purpose**: Categorizes notifications by type or purpose
- **Values**: String codes like 'follow_request', 'achievement_unlocked'
- **Usage**: Used for filtering or processing notifications differently
- **Behavior**: Application code can use this to determine notification handling

### `notified_object_type` and `notified_object_id`
- **Purpose**: Polymorphic association to the object the notification is about
- **Values**:
  - `notified_object_type`: Class name (e.g., 'Team', 'Achievement')
  - `notified_object_id`: ID of the referenced object
- **Usage**: Links notifications to relevant objects in the system
- **Behavior**: Allows notifications to reference any model in the application

### `attachment`
- **Purpose**: Stores reference to attached files
- **Values**: String containing file path or identifier
- **Usage**: Allows sending files with messages
- **Behavior**: Implementation details depend on the application's file storage system

### `global`
- **Purpose**: Indicates if the notification is system-wide
- **Values**: Boolean, defaults to false
- **Usage**: Distinguishes between targeted and broadcast notifications
- **Behavior**: Global notifications might be shown to all users

### `expires`
- **Purpose**: Sets an expiration date for the notification
- **Values**: Datetime or null
- **Usage**: Allows temporary notifications
- **Behavior**: Expired notifications might be automatically hidden or deleted

## Detailed Column Explanations for `mailboxer_receipts`

### `id`
- **Purpose**: Primary key for uniquely identifying each receipt
- **Values**: Auto-incrementing integer
- **Usage**: Used in queries about message delivery status

### `receiver_type` and `receiver_id`
- **Purpose**: Polymorphic association identifying the recipient
- **Values**:
  - `receiver_type`: Class name (typically 'User')
  - `receiver_id`: ID of the recipient object
- **Usage**: Links receipts to the entities that received the notification
- **Behavior**: Allows messages to be received by different types of entities

### `notification_id`
- **Purpose**: Foreign key to the notification this receipt is for
- **Values**: Integer referencing a notification ID
- **Usage**: Links the receipt to its notification
- **Behavior**: Each notification has one receipt per recipient

### `is_read`
- **Purpose**: Tracks whether the recipient has read the notification
- **Values**: Boolean, defaults to false
- **Usage**: Used for unread message counts and highlighting
- **Behavior**: Updated when the recipient views the message

### `trashed`
- **Purpose**: Indicates if the recipient moved the message to trash
- **Values**: Boolean, defaults to false
- **Usage**: Used for message filtering and display
- **Behavior**: Messages can be trashed without being permanently deleted

### `deleted`
- **Purpose**: Indicates if the recipient deleted the message
- **Values**: Boolean, defaults to false
- **Usage**: Used for message filtering and display
- **Behavior**: Deleted messages might be hidden but retained in the database

### `mailbox_type`
- **Purpose**: Categorizes which mailbox the message belongs to
- **Values**: String (25 chars), typically 'inbox' or 'sentbox'
- **Usage**: Used for message filtering and organization
- **Behavior**: Determines where the message appears in the UI

### `is_delivered`
- **Purpose**: Tracks whether the notification has been delivered
- **Values**: Boolean, defaults to false
- **Usage**: Used for delivery confirmation
- **Behavior**: Updated when the notification is successfully delivered

### `delivery_method`
- **Purpose**: Records how the notification was delivered
- **Values**: String like 'email', 'database', etc.
- **Usage**: Used for tracking delivery channels
- **Behavior**: May affect how the notification is displayed or processed

### `message_id`
- **Purpose**: External identifier for the message
- **Values**: String, possibly an email message ID
- **Usage**: Used for email threading and tracking
- **Behavior**: May be used to correlate with external messaging systems

## Detailed Column Explanations for `mailboxer_conversation_opt_outs`

### `id`
- **Purpose**: Primary key for uniquely identifying each opt-out record
- **Values**: Auto-incrementing integer
- **Usage**: Used in queries about conversation preferences

### `unsubscriber_type` and `unsubscriber_id`
- **Purpose**: Polymorphic association identifying who opted out
- **Values**:
  - `unsubscriber_type`: Class name (typically 'User')
  - `unsubscriber_id`: ID of the unsubscriber object
- **Usage**: Links opt-outs to the entities that unsubscribed
- **Behavior**: Prevents further notifications from the conversation

### `conversation_id`
- **Purpose**: Foreign key to the conversation being opted out from
- **Values**: Integer referencing a conversation ID
- **Usage**: Identifies which conversation the user no longer wants to receive
- **Behavior**: Messages from this conversation won't generate notifications for the unsubscriber
