# API Endpoints Documentation

This document lists all API endpoints available in the project, grouped by functionality. Each endpoint includes the HTTP method, URL, required request body (if any), and headers for frontend integration and testing.

---

## Authentication (Prefix: `/auth`)

| Method | Endpoint                                   | Description                                 | Body (JSON) Fields                                                                 | Headers                        |
|--------|--------------------------------------------|---------------------------------------------|-------------------------------------------------------------------------------------|-------------------------------|
| POST   | /auth/register                             | Register a new user                         | name, email, password, password_confirmation                                        | Content-Type: application/json |
| POST   | /auth/login                                | Login user                                  | email, password                                                                     | Content-Type: application/json |
| GET    | /auth/google/redirect                      | Google OAuth redirect                       | -                                                                                   | -                             |
| GET    | /auth/google/callback                      | Google OAuth callback                       | -                                                                                   | -                             |
| DELETE | /auth/logout                               | Logout user                                 | -                                                                                   | Authorization: Bearer {token}  |
| GET    | /auth/email/verify/{id}/{hash}             | Verify email                                | -                                                                                   | -                             |
| POST   | /auth/email/verification-notification       | Resend verification email                   | -                                                                                   | Authorization: Bearer {token}  |
| POST   | /auth/change-password                      | Change password                             | current_password, password, password_confirmation                                   | Authorization: Bearer {token}  |
| POST   | /auth/forgot-password                      | Request password reset                      | email                                                                              | Content-Type: application/json |
| POST   | /auth/reset-password                       | Reset password                              | email, token, password, password_confirmation                                       | Content-Type: application/json |
| GET    | /auth/me                                   | Get current user info                       | -                                                                                   | Authorization: Bearer {token}  |

---

## Quizzes

| Method | Endpoint                                   | Description                                 | Body (JSON) Fields                                                                 | Headers                        |
|--------|--------------------------------------------|---------------------------------------------|-------------------------------------------------------------------------------------|-------------------------------|
| GET    | /quizzes                                   | List all quizzes                            | -                                                                                   | Authorization: Bearer {token}  |
| POST   | /quizzes                                   | Create a new quiz                           | title (string), description (string, optional), set_time_limit (int, optional), status (string: active/draft/closed, optional) | Authorization: Bearer {token}  |
| POST   | /quizzes                                   | Create a new quiz                           | title (string, e.g. "Quiz 01"), description (string, optional, e.g. "Math quiz"), set_time_limit (integer, optional, e.g. 60), status (string: active/draft/closed, optional, e.g. "active") | Authorization: Bearer {token}  |
| GET    | /quizzes/{quiz}                            | Get quiz details                            | -                                                                                   | Authorization: Bearer {token}  |
| PUT    | /quizzes/{quiz}                            | Update quiz                                 | title (string), description (string, optional), set_time_limit (int, optional), status (string: active/draft/closed, optional) | Authorization: Bearer {token}  |
| PUT    | /quizzes/{quiz}                            | Update quiz                                 | title (string, e.g. "Quiz 01"), description (string, optional), set_time_limit (integer, optional), status (string: active/draft/closed, optional) | Authorization: Bearer {token}  |
| PATCH  | /quizzes/{quiz}                            | Partially update quiz                       | Any updatable quiz fields                                                           | Authorization: Bearer {token}  |
| PATCH  | /quizzes/{quiz}                            | Partially update quiz                       | Any updatable quiz fields (see above for field types)                                | Authorization: Bearer {token}  |
| DELETE | /quizzes/{quiz}                            | Delete quiz                                 | 
-                                                                                   | Authorization: Bearer {token}  |
| GET    | /quizzes/{quiz}/questions                  | List questions for a quiz                   | -                                                                                   | Authorization: Bearer {token}  |
| POST   | /quizzes/{quiz}/questions                  | Add question to a quiz                      | question_text (string), type_id (int), options (array, required for MCQ), answer (string/array, required for non-MCQ), score (int, optional) | Authorization: Bearer {token}  |
| POST   | /quizzes/{quiz}/questions                  | Add question to a quiz                      | question_text (string, e.g. "What is 2+2?"), type_id (integer, e.g. 1), options (array of objects, required for MCQ: [{"text": "2"}, {"text": "4"}, {"text": "3"}]), answer (string or array, e.g. "4" or ["A", "B"]), score (integer, optional, e.g. 5) | Authorization: Bearer {token}  |
| GET    | /quizzes/join-quiz/{password}              | Find quiz by password                       | -                                                                                   | -                             |
| POST   | /quizzes/{quiz}/submit                     | Submit quiz answers                         | answers: [{question_id, answer(s)}]                                                | Content-Type: application/json |
| GET    | /quizzes/{quiz}/submission                 | Get quiz submission                         | -                                                                                   | -                             |
| GET    | /quizzes/{quiz}/cheats/summary             | Get quiz cheats summary                     | -                                                                                   | Authorization: Bearer {token}  |

---

## Questions

| Method | Endpoint                                   | Description                                 | Body (JSON) Fields                                                                 | Headers                        |
|--------|--------------------------------------------|---------------------------------------------|-------------------------------------------------------------------------------------|-------------------------------|
| GET    | /questions                                 | List all questions                          | -                                                                                   | Authorization: Bearer {token}  |
| GET    | /questions/{question}                      | Get question details                        | -                                                                                   | Authorization: Bearer {token}  |
| PUT    | /questions/{question}                      | Update question                             | question_text, type_id, ... (see Question model below)                              | Authorization: Bearer {token}  |

### Question Model Fields

| Field         | Type         | Description                                      |
|-------------- |------------- |-------------------------------------------------|
| id            | integer      | Primary key                                      |
| quiz_id       | integer      | Foreign key to quizzes table                     |
| question_text | string       | The text of the question                         |
| type_id       | integer      | Foreign key to question_types table              |
| options       | json/null    | Array of options (required for MCQ, nullable)    |
| answer        | json/string  | Correct answer(s), string or array               |
| score         | integer      | Score for the question (optional)                |
| created_at    | timestamp    | Timestamp when created                           |
| updated_at    | timestamp    | Timestamp when last updated                      |
| PUT    | /questions/{question}                      | Update question                             | question_text (string), type_id (integer), options (array of objects, optional), answer (string/array), score (integer, optional) | Authorization: Bearer {token}  |
| PATCH  | /questions/{question}                      | Partially update question                   | Any updatable question fields                                                       | Authorization: Bearer {token}  |
| DELETE | /questions/{question}                      | Delete question                             | -                                                                                   | Authorization: Bearer {token}  |

---

## Cheats

| Method | Endpoint                                   | Description                                 | Body (JSON) Fields                                                                 | Headers                        |
|--------|--------------------------------------------|---------------------------------------------|-------------------------------------------------------------------------------------|-------------------------------|
| POST   | /cheats                                    | Store a cheat record                        | participant_id (int), quiz_id (int), type (string: tab-switch/copy-paste/other), detected_at (datetime, optional), details (string, optional) | Content-Type: application/json |
| POST   | /cheats                                    | Store a cheat record                        | participant_id (integer, e.g. 12), quiz_id (integer, e.g. 3), type (string: "tab-switch"/"copy-paste"/"other"), detected_at (ISO datetime, optional, e.g. "2026-05-27T09:51:23Z"), details (string, optional, e.g. "User switched tab 3 times") | Content-Type: application/json |
| POST   | /quizzes/{quiz}/submit                     | Submit quiz answers                         | answers (array of objects): [{question_id (integer, e.g. 1), answer (string/array, e.g. "4" or ["A", "B"])}] | Content-Type: application/json |
| GET    | /participants/{participant}/cheats          | List cheats by participant                  | -                                                                                   | Authorization: Bearer {token}  |

---

## Participants

| Method | Endpoint                                   | Description                                 | Body (JSON) Fields                                                                 | Headers                        |
|--------|--------------------------------------------|---------------------------------------------|-------------------------------------------------------------------------------------|-------------------------------|
| POST   | /quizzes/{quiz}/submit                     | Submit answers for a quiz                   | answers: [{question_id, answer(s)}]                                                | Content-Type: application/json |
| GET    | /quizzes/{quiz}/submission                 | Get submission for a quiz                   | -                                                                                   | -                             |

---


---

## Example Request Bodies

### Create Quiz
```
POST /quizzes
{
	"title": "Quiz 01",
	"description": "Math quiz",
	"set_time_limit": 60,
	"status": "active"
}
```

### Add Question to Quiz (MCQ)
```
POST /quizzes/3/questions
{
	"question_text": "What is 2+2?",
	"type_id": 1,
	"options": [
		{"text": "2"},
		{"text": "4"},
		{"text": "3"}
	],
	"answer": "4",
	"score": 5
}
```

### Add Question to Quiz (Short Answer)
```
POST /quizzes/3/questions
{
	"question_text": "Name a prime number between 10 and 20.",
	"type_id": 2,
	"answer": "13",
	"score": 5
}
```

### Submit Quiz Answers
```
POST /quizzes/3/submit
{
	"answers": [
		{"question_id": 1, "answer": "4"},
		{"question_id": 2, "answer": ["A", "B"]}
	]
}
```

### Store Cheat Record
```
POST /cheats
{
	"participant_id": 12,
	"quiz_id": 3,
	"type": "tab-switch",
	"detected_at": "2026-05-27T09:51:23Z",
	"details": "User switched tab 3 times"
}
```

---

**Notes:**
- `{quiz}`, `{question}`, `{participant}`, `{id}`, `{hash}`, and `{password}` are path parameters and should be replaced with actual values.
- Endpoints marked with `Authorization: Bearer {token}` require authentication. Obtain the token from login/register responses.
- `Content-Type: application/json` is required for all POST/PUT/PATCH requests with a JSON body.
- Throttle middleware limits the number of requests per minute.


_Last updated: May 27, 2026_
