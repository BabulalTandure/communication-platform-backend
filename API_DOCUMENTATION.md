# Communication Platform — Backend API & Integration Documentation

This document serves as the authoritative API and WebSocket reference for the **Laravel Backend** consumed by the **Flutter Frontend**.

---

## 1. General Configuration

- **Base REST API URL**: `http://127.0.0.1:8000/api`
- **Default HTTP Headers**:
  - `Accept: application/json`
  - `Content-Type: application/json` (Use `multipart/form-data` for file uploads)
- **Authentication Header**:
  ```http
  Authorization: Bearer <sanctum_access_token>
  ```

---

## 2. Standard JSON Response Formats

### Success Response Structure (HTTP 200 OK / 201 Created)
```json
{
    "success": true,
    "message": "Human-readable success message.",
    "data": {}
}
```

### Error Response Structure (HTTP 400 / 401 / 403 / 404 / 422)
```json
{
    "success": false,
    "message": "Human-readable error message.",
    "errors": {}
}
```

---

## 3. Authentication APIs (`/api/auth`)

### 3.1 Login
- **Endpoint**: `POST /api/auth/login`
- **Auth Required**: No (Public)
- **Request Body**:
  ```json
  {
      "username": "admin",
      "password": "password123"
  }
  ```
- **Success Response (200 OK)**:
  ```json
  {
      "success": true,
      "message": "Login successful.",
      "data": {
          "token": "1|xzT346Jq...",
          "token_type": "Bearer",
          "user": {
              "id": 1,
              "username": "admin",
              "role": "admin",
              "status": "active",
              "created_by": null,
              "created_at": "2026-09-12T14:53:23+00:00",
              "updated_at": "2026-09-12T14:53:23+00:00"
          }
      }
  }
  ```
- **Error Responses**:
  - **401 Unauthorized**: Invalid credentials.
  - **403 Forbidden**: Account is disabled.

### 3.2 Authenticated User Profile
- **Endpoint**: `GET /api/auth/me`
- **Auth Required**: Yes (`auth:sanctum`, active status)
- **Success Response (200 OK)**:
  ```json
  {
      "success": true,
      "message": "Profile retrieved successfully.",
      "data": {
          "id": 1,
          "username": "admin",
          "role": "admin",
          "status": "active"
      }
  }
  ```

### 3.3 Logout
- **Endpoint**: `POST /api/auth/logout`
- **Auth Required**: Yes (`auth:sanctum`, active status)
- **Success Response (200 OK)**:
  ```json
  {
      "success": true,
      "message": "Logged out successfully.",
      "data": {}
  }
  ```

---

## 4. Admin User Management APIs (`/api/users`)

*All routes under `/api/users` require `auth:sanctum`, active status, and `admin` role.*

### 4.1 Create User Account
- **Endpoint**: `POST /api/users`
- **Request Body**:
  ```json
  {
      "username": "john_doe",
      "password": "secretpassword",
      "role": "user",
      "status": "active"
  }
  ```
- **Success Response (201 Created)**:
  ```json
  {
      "success": true,
      "message": "User created successfully.",
      "data": {
          "id": 2,
          "username": "john_doe",
          "role": "user",
          "status": "active",
          "created_by": 1
      }
  }
  ```

### 4.2 List Users
- **Endpoint**: `GET /api/users?search=john&role=user&status=active`
- **Success Response (200 OK)**

### 4.3 View User Details
- **Endpoint**: `GET /api/users/{id}`

### 4.4 Enable / Disable User Account
- **Endpoint**: `PATCH /api/users/{id}/status`
- **Request Body**:
  ```json
  {
      "status": "disabled"
  }
  ```
- **Behavior**: Setting `status` to `disabled` immediately revokes all active Sanctum tokens for that user.

---

## 5. Group Management APIs (`/api/groups`)

### 5.1 List User Joined Groups (Normal User)
- **Endpoint**: `GET /api/groups/my-groups`
- **Auth Required**: Yes (`auth:sanctum`, active status)

### 5.2 List All Groups (Admin Only)
- **Endpoint**: `GET /api/groups`
- **Auth Required**: Yes (`auth:sanctum`, active status, `admin` role)

### 5.3 Create Group (Admin Only)
- **Endpoint**: `POST /api/groups`
- **Content-Type**: `multipart/form-data` or `application/json`
- **Request Parameters**:
  - `name` (string, required)
  - `user_ids` (array of integer user IDs, optional)
  - `profile_image` (file upload, optional, max 5MB)
- **Success Response (201 Created)**:
  ```json
  {
      "success": true,
      "message": "Group created successfully.",
      "data": {
          "id": 1,
          "name": "Engineering Team",
          "profile_image": "http://127.0.0.1:8000/storage/groups/images/sample.jpg",
          "created_by": 1,
          "chat_id": 10,
          "members": [...]
      }
  }
  ```

### 5.4 View Group Details (Admin Only)
- **Endpoint**: `GET /api/groups/{id}`

### 5.5 Update Group Details (Admin Only)
- **Endpoint**: `PUT /api/groups/{id}` or `PATCH /api/groups/{id}`

### 5.6 Add Members to Group (Admin Only)
- **Endpoint**: `POST /api/groups/{id}/members`
- **Request Body**:
  ```json
  {
      "user_ids": [2, 3]
  }
  ```

### 5.7 Remove Member from Group (Admin Only)
- **Endpoint**: `DELETE /api/groups/{id}/members/{userId}`

### 5.8 Delete Group (Admin Only)
- **Endpoint**: `DELETE /api/groups/{id}`

---

## 6. Private Chat APIs (`/api/chats`)

### 6.1 Create or Retrieve Private Chat
- **Endpoint**: `POST /api/chats/private`
- **Auth Required**: Yes (`auth:sanctum`, active status)
- **Request Body**:
  ```json
  {
      "recipient_id": 2
  }
  ```
- **Behavior**: Reuses existing private chat if one already exists between the two users.
- **Success Response (200 / 201)**:
  ```json
  {
      "success": true,
      "message": "Private chat retrieved successfully.",
      "data": {
          "id": 1,
          "type": "private",
          "recipient": {
              "id": 2,
              "username": "john_doe"
          }
      }
  }
  ```

### 6.2 List Active Chats
- **Endpoint**: `GET /api/chats`

### 6.3 View Chat Details
- **Endpoint**: `GET /api/chats/{id}`
- **Behavior**: Enforces participant authorization. Non-participants receive `403 Forbidden`.

---

## 7. Messaging APIs (`/api/chats/{id}/messages`)

### 7.1 Send Text Message
- **Endpoint**: `POST /api/chats/{id}/messages`
- **Content-Type**: `application/json`
- **Request Body**:
  ```json
  {
      "message_type": "text",
      "message_text": "Hello world!"
  }
  ```

### 7.2 Send Image Message
- **Endpoint**: `POST /api/chats/{id}/messages`
- **Content-Type**: `multipart/form-data`
- **Request Parameters**:
  - `message_type`: `image` (required)
  - `file`: Image file (`jpeg, png, jpg, webp, gif`, max size 5120 KB, required)
  - `message_text`: Caption (optional)

### 7.3 Send Voice Note Message
- **Endpoint**: `POST /api/chats/{id}/messages`
- **Content-Type**: `multipart/form-data`
- **Request Parameters**:
  - `message_type`: `voice` (required)
  - `file`: Audio file (`mp3, ogg, wav, x-wav, mp4, aac, webm, m4a`, max size 10240 KB, required)

### 7.4 Fetch Chat Message History
- **Endpoint**: `GET /api/chats/{id}/messages?page=1`
- **Success Response (200 OK)**:
  ```json
  {
      "success": true,
      "message": "Messages retrieved successfully.",
      "data": {
          "messages": [
              {
                  "id": 14,
                  "chat_id": 1,
                  "sender_id": 2,
                  "sender": {
                      "id": 2,
                      "username": "john_doe",
                      "role": "user"
                  },
                  "message_type": "voice",
                  "message_text": null,
                  "file_path": "messages/voice_notes/sample.wav",
                  "file_url": "http://127.0.0.1:8000/storage/messages/voice_notes/sample.wav",
                  "file_name": "note.wav",
                  "file_size": 24820,
                  "created_at": "2026-09-12T15:05:15+00:00",
                  "updated_at": "2026-09-12T15:05:15+00:00"
              }
          ],
          "pagination": {
              "current_page": 1,
              "last_page": 1,
              "per_page": 30,
              "total": 1
          }
      }
  }
  ```

---

## 8. Username Change Request APIs (`/api/username-change-requests`)

### 8.1 Submit Username Change Request (Normal User)
- **Endpoint**: `POST /api/username-change-requests`
- **Request Body**:
  ```json
  {
      "requested_username": "john_new_name"
  }
  ```
- **Rules**: Validates requested username uniqueness. Rejects request if user already has a pending request (`422 Unprocessable Entity`).

### 8.2 View My Requests (Normal User)
- **Endpoint**: `GET /api/username-change-requests/my-requests`

### 8.3 List Requests (Admin Only)
- **Endpoint**: `GET /api/username-change-requests?status=pending`

### 8.4 Approve Request (Admin Only)
- **Endpoint**: `POST /api/username-change-requests/{id}/approve`
- **Behavior**: Re-verifies username uniqueness and updates user's `username` field in `users` table upon approval.

### 8.5 Reject Request (Admin Only)
- **Endpoint**: `POST /api/username-change-requests/{id}/reject`

---

## 9. Real-Time WebSocket Integration Specs for Flutter

### Event & Channel Specifications
- **Broadcasting Event Class**: `App\Events\MessageSent`
- **Broadcast Contract**: `ShouldBroadcastNow` (Dispatched immediately without queue delay)
- **Channel Type**: `PrivateChannel`
- **Channel Name in Laravel Code**: `chat.{chat_id}`
- **Pusher/Reverb Protocol Wire Channel Name**: `private-chat.{chat_id}`
- **Broadcast Event Name**: `message.sent`

### Payload Structure Broadcasted via WebSocket
```json
{
    "id": 14,
    "chat_id": 1,
    "sender_id": 2,
    "sender": {
        "id": 2,
        "username": "john_doe",
        "role": "user"
    },
    "message_type": "voice",
    "message_text": null,
    "file_path": "messages/voice_notes/sample.wav",
    "file_url": "http://127.0.0.1:8000/storage/messages/voice_notes/sample.wav",
    "file_name": "note.wav",
    "file_size": 24820,
    "created_at": "2026-09-12T15:05:15+00:00",
    "updated_at": "2026-09-12T15:05:15+00:00"
}
```

### Channel Authorization
Subscribing to `private-chat.{chat_id}` via Pusher / Laravel Echo requires authenticating the socket connection using Sanctum Bearer Token via `/broadcasting/auth`.
