# 🚀 Quick Start Guide - EdLibya API

## Server is Running! ✅

Your Laravel API server is now running at:
```
http://localhost:8000
```

Base API URL:
```
http://localhost:8000/api
```

---

## 🧪 Test the API

### 1. Login as Student

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d "{\"email\":\"student@edlibya.com\",\"password\":\"password\"}"
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "user": {...},
    "access_token": "1|...",
    "token_type": "Bearer"
  }
}
```

**Save the `access_token` for next requests!**

### 2. Get All Courses (Public)

```bash
curl http://localhost:8000/api/courses
```

### 3. Get Categories (Public)

```bash
curl http://localhost:8000/api/categories
```

### 4. Get Your Profile (Authenticated)

Replace `YOUR_TOKEN` with the token from step 1:

```bash
curl http://localhost:8000/api/auth/user \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 5. Get Categories (Public)

```bash
curl http://localhost:8000/api/categories
```

---

## 👥 Test Users

### Student
```
Email: student@edlibya.com
Password: password
```

### Teacher
```
Email: teacher@edlibya.com
Password: password
```

### Admin
```
Email: admin@edlibya.com
Password: password
```

---

## 🔑 Using in Postman or Browser

### Testing with Browser Extensions:

1. **Install a REST client** (like REST Client for VS Code, or use Postman)

2. **Create a request file** (e.g., `api-tests.http`):

```http
### Login as Student
POST http://localhost:8000/api/auth/login
Content-Type: application/json

{
  "email": "student@edlibya.com",
  "password": "password"
}

###

### Register New User
POST http://localhost:8000/api/auth/register
Content-Type: application/json

{
  "name": "Test User",
  "email": "test@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}

###

### Get All Courses
GET http://localhost:8000/api/courses

###

### Get Categories
GET http://localhost:8000/api/categories

###

### Get User Profile (requires token)
GET http://localhost:8000/api/auth/user
Authorization: Bearer YOUR_TOKEN_HERE

###

### Get Admin Dashboard (requires admin token)
GET http://localhost:8000/api/admin/dashboard
Authorization: Bearer YOUR_ADMIN_TOKEN_HERE

###

### Get My Courses (requires token)
GET http://localhost:8000/api/student/my-courses
Authorization: Bearer YOUR_TOKEN_HERE

###

### Generate Credit Cards (Admin only)
POST http://localhost:8000/api/admin/credit-cards/generate
Content-Type: application/json
Authorization: Bearer YOUR_ADMIN_TOKEN_HERE

{
  "count": 10,
  "value": 50
}

###

### Redeem Credit Card
POST http://localhost:8000/api/payment/credit-cards/redeem
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN_HERE

{
  "serial_number": "CARD-2025-XXXXXX"
}
```

---

## 📱 Connect Your Flutter App

Update your Flutter app's API service:

```dart
// In your api_service.dart or similar file
class ApiService {
  static const String baseUrl = 'http://localhost:8000/api';
  
  Future<Map<String, dynamic>> login(String email, String password) async {
    final response = await http.post(
      Uri.parse('$baseUrl/auth/login'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({
        'email': email,
        'password': password,
      }),
    );
    return jsonDecode(response.body);
  }
  
  Future<Map<String, dynamic>> getCourses() async {
    final response = await http.get(Uri.parse('$baseUrl/courses'));
    return jsonDecode(response.body);
  }
}
```

**Note**: For Android emulator, use `10.0.2.2:8000` instead of `localhost:8000`

---

## 🎯 Common Endpoints

| Endpoint | Method | Auth Required | Description |
|----------|--------|---------------|-------------|
| `/api/auth/login` | POST | No | Login |
| `/api/auth/register` | POST | No | Register |
| `/api/auth/user` | GET | Yes | Get current user |
| `/api/courses` | GET | No | Get all courses |
| `/api/courses/{id}` | GET | No | Get course details |
| `/api/student/my-courses` | GET | Yes (Student) | Get my enrolled courses |
| `/api/student/courses/{id}/enroll` | POST | Yes (Student) | Enroll in course |
| `/api/payment/credit-balance` | GET | Yes | Get credit balance |
| `/api/payment/credit-cards/redeem` | POST | Yes | Redeem credit card |
| `/api/admin/dashboard` | GET | Yes (Admin) | Admin dashboard |
| `/api/admin/users` | GET | Yes (Admin) | Get all users |
| `/api/admin/credit-cards` | GET | Yes (Admin) | Get credit cards |
| `/api/admin/credit-cards/generate` | POST | Yes (Admin) | Generate cards |

**See `README.md` for complete list!**

---

## 🛠️ Useful Commands

```bash
# View all routes
php artisan route:list

# Clear cache
php artisan cache:clear

# Reset database (WARNING: Deletes all data)
php artisan migrate:fresh --seed

# View logs
tail -f storage/logs/laravel.log
```

---

## ✅ Next Steps

1. ✅ Test authentication endpoints
2. ✅ Connect your Flutter app
3. ✅ Generate credit cards (as admin)
4. ✅ Test course enrollment
5. ✅ Test payment system

---

## 🐛 Troubleshooting

### Server not starting?
```bash
cd backend
php artisan serve
```

### Port already in use?
```bash
php artisan serve --port=8001
```

### Database issues?
```bash
php artisan migrate:fresh --seed
```

---

**Happy coding! 🎉**

