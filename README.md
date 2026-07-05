# EdLibya - Learning Management System API

## نظام إدارة التعليم - API

### نظرة عامة

نظام إدارة تعليمي شامل مبني باستخدام Laravel API مع دعم كامل للغة العربية والإنجليزية. يتضمن إدارة الدورات، الطلاب، المحاضرين، المدفوعات، الكوبونات، وإدارة الدعم الفني.

### المميزات الرئيسية

1. **إدارة الدورات والكورسات**
2. **إدارة المستخدمين (Admin, Teacher, Student)**
3. **نظام المدفوعات برصيد الكروت**
4. **إدارة الكوبونات والخصومات**
5. **دعم فني عبر التذاكر**
6. **لوحة تحكم إدارية شاملة**
7. **نظام الصلاحيات والأدوار**
8. **تقارير وإحصائيات متقدمة**





| Role | Email | Password |
|------|-------|----------|
| Admin | admin@noon.ly | Admin1234 |
| Teacher | teacher@noon.ly | Teacher1234 |
| Student | student@noon.ly | Student1234 |

**Legacy Test Accounts (edlibya.com):**

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@edlibya.com | password |
| Student | student@edlibya.com | password |
| Student 2 | mohamed@edlibya.com | password |
| Student 3 | layla@edlibya.com | password |


---

## التثبيت والإعداد

### المتطلبات
- PHP 8.2+
- Composer
- SQLite
- Laravel 12+

### خطوات التثبيت

```bash
# الانتقال إلى مجلد backend
cd backend

# تثبيت المكتبات
composer install

# إعداد ملف البيئة
cp .env.example .env

# توليد مفتاح التطبيق
php artisan key:generate

# تشغيل migrations
php artisan migrate

# تشغيل seeders
php artisan db:seed

# تشغيل السيرفر
php artisan serve
```

### بيانات الدخول الافتراضية

**Use these accounts for testing:**

- **Admin**: admin@noon.ly / Admin1234
- **Teacher**: teacher@noon.ly / Teacher1234
- **Student**: student@noon.ly / Student1234

**Or use legacy accounts:**

- **Admin**: admin@edlibya.com / password
- **Teacher**: teacher@edlibya.com / password
- **Student**: student@edlibya.com / password

---

## API Documentation

### Base URL
```
http://localhost:8000/api
```

### Authentication

يستخدم النظام Laravel Sanctum للتوثيق. يجب إرسال token في header لكل طلب محمي:

```
Authorization: Bearer {token}
```

---

## Auth Endpoints

### 1. Register - تسجيل جديد
```
POST /api/auth/register

Body:
{
    "name": "محمد",
    "email": "mohammed@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "phone": "0912345678"
}

Response:
{
    "success": true,
    "message": "User registered successfully",
    "data": {
        "user": {...},
        "access_token": "1|...",
        "token_type": "Bearer"
    }
}
```

### 2. Login - تسجيل دخول
```
POST /api/auth/login

Body:
{
    "email": "student@edlibya.com",
    "password": "password"
}

Response:
{
    "success": true,
    "data": {
        "user": {...},
        "access_token": "1|...",
        "token_type": "Bearer"
    }
}
```

### 3. Logout - تسجيل خروج
```
POST /api/auth/logout
Headers: Authorization: Bearer {token}
```

### 4. Get User Profile
```
GET /api/auth/user
Headers: Authorization: Bearer {token}

Response:
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "name": "Student",
            "email": "student@edlibya.com",
            "roles": [...],
            "credits": {
                "balance": 0
            }
        }
    }
}
```

---

## Courses Endpoints

### 1. Get All Courses - عرض جميع الدورات
```
GET /api/courses

Query Parameters:
- category: Filter by category
- level: Filter by level (beginner, intermediate, advanced)
- status: Filter by status
- search: Search in title/description
- page: Page number

Response:
{
    "success": true,
    "data": {
        "courses": [...],
        "current_page": 1,
        "total": 50
    }
}
```

### 2. Get Course Details
```
GET /api/courses/{id}
```

### 3. Enroll in Course
```
POST /api/student/courses/{course_id}/enroll
Headers: Authorization: Bearer {token}

Response:
{
    "success": true,
    "message": "Enrolled successfully"
}
```

### 4. My Courses (Student)
```
GET /api/student/my-courses
Headers: Authorization: Bearer {token}
```

### 5. Add Review
```
POST /api/student/courses/{course_id}/reviews
Headers: Authorization: Bearer {token}

Body:
{
    "rating": 5,
    "review": "دورة ممتازة"
}
```

---

## Payment Endpoints

### 1. Get Credit Balance
```
GET /api/payment/credit-balance
Headers: Authorization: Bearer {token}

Response:
{
    "success": true,
    "data": {
        "balance": 100
    }
}
```

### 2. Redeem Credit Card
```
POST /api/payment/credit-cards/redeem
Headers: Authorization: Bearer {token}

Body:
{
    "serial_number": "CARD-2025-ABC123"
}

Response:
{
    "success": true,
    "message": "Credit card redeemed successfully",
    "data": {
        "credit_added": 50
    }
}
```

### 3. My Transactions
```
GET /api/payment/transactions
Headers: Authorization: Bearer {token}

Response:
{
    "success": true,
    "data": {
        "transactions": [...]
    }
}
```

---

## Admin Endpoints

### 1. Dashboard
```
GET /api/admin/dashboard
Headers: Authorization: Bearer {token} (Admin only)

Response:
{
    "success": true,
    "data": {
        "total_users": 150,
        "total_courses": 25,
        "total_revenue": 50000,
        "pending_courses": 5,
        "active_students": 120,
        "monthly_revenue": [...]
    }
}
```

### 2. Users Management
```
GET /api/admin/users
GET /api/admin/users/{id}
PUT /api/admin/users/{id}
DELETE /api/admin/users/{id}
POST /api/admin/users/{id}/toggle-status
```

### 3. Courses Management
```
GET /api/admin/courses/pending
POST /api/admin/courses/{id}/approve
POST /api/admin/courses/{id}/reject

Body for reject:
{
    "reason": "المحتوى غير مناسب"
}
```

### 4. Credit Cards Management
```
GET /api/admin/credit-cards
POST /api/admin/credit-cards/generate

Body:
{
    "count": 10,
    "value": 50  // 10, 25, 50, 100, 250
}

Response:
{
    "success": true,
    "message": "Credit cards generated successfully",
    "data": {
        "cards": [...]
    }
}
```

### 5. Categories Management
```
GET /api/admin/categories
POST /api/admin/categories
PUT /api/admin/categories/{id}
DELETE /api/admin/categories/{id}

Body:
{
    "name": "برمجة",
    "slug": "programming",
    "description": "...",
    "image": "...",
    "parent_id": null
}
```

### 6. Coupons Management
```
GET /api/admin/coupons
POST /api/admin/coupons
PUT /api/admin/coupons/{id}
DELETE /api/admin/coupons/{id}

Body:
{
    "code": "SUMMER2025",
    "name": "تخفيض الصيف",
    "discount_type": "percentage", // or "fixed_amount"
    "discount_value": 20,
    "minimum_purchase": 100,
    "maximum_discount": 50,
    "usage_limit": 100,
    "expires_at": "2025-08-31 23:59:59"
}
```

### 7. Withdraw Requests
```
GET /api/admin/withdraw-requests
POST /api/admin/withdraw-requests/{id}/approve
POST /api/admin/withdraw-requests/{id}/reject

Body for reject:
{
    "reason": "..."
}
```

### 8. Analytics
```
GET /api/admin/analytics/overview
GET /api/admin/analytics/courses
GET /api/admin/analytics/users
GET /api/admin/analytics/revenue
```

### 9. Activity Logs
```
GET /api/admin/activity-logs
Query: user_id, action, date_from, date_to
```

---

## Teacher Endpoints

### 1. Create Course
```
POST /api/teacher/courses
Headers: Authorization: Bearer {token}

Body:
{
    "title": "دورة تعلم Laravel",
    "slug": "laravel-course",
    "description": "...",
    "short_description": "...",
    "category_id": 1,
    "price": 100,
    "discount_price": 80,
    "level": "intermediate",
    "language": "ar",
    "tags": ["Laravel", "PHP"],
    "requirements": ["معرفة أساسية بـ PHP"],
    "what_you_will_learn": ["..."]
}
```

### 2. Update Course
```
PUT /api/teacher/courses/{id}
```

### 3. Delete Course
```
DELETE /api/teacher/courses/{id}
```

### 4. Teacher Dashboard
```
GET /api/teacher/dashboard

Response:
{
    "success": true,
    "data": {
        "total_courses": 10,
        "total_students": 150,
        "total_earnings": 5000,
        "pending_courses": 2
    }
}
```

---

## Support Endpoints

### 1. Create Ticket
```
POST /api/support/tickets
Headers: Authorization: Bearer {token}

Body:
{
    "subject": "مشكلة في التسجيل",
    "description": "...",
    "priority": "high",
    "category": "technical"
}
```

### 2. My Tickets
```
GET /api/support/tickets
```

### 3. Ticket Details
```
GET /api/support/tickets/{id}
```

---

## Response Format

جميع الاستجابات تتبع نفس الهيكل:

```json
{
    "success": true,
    "message": "Operation successful",
    "data": {
        // Response data
    }
}
```

### Error Response
```json
{
    "success": false,
    "message": "Error message",
    "errors": {
        "field": ["Error message"]
    }
}
```

---

## Database Schema

### Users
- Basic user information
- Phone, avatar, bio, specialization
- is_verified, is_active flags
- last_login tracking

### Roles & Permissions
- Roles: admin, teacher, student, support
- Many-to-many relationship
- Permission-based access control

### Courses
- Complete course information
- Status: draft, pending, published, rejected
- Price, discount, ratings
- Category relationship

### Credits & Transactions
- User credit balance
- Transaction history
- Credit card system (10, 25, 50, 100, 250 LYD)
- Commission tracking

### Course Content
- Sections and Lessons
- Video/Document/Quiz support
- Preview lessons

### Reviews
- Rating and review system
- One review per user per course

### Support Tickets
- Full ticket management
- Priority and category
- Assignment to support staff

### Activity Logs
- Complete audit trail
- IP tracking, user actions
- Model changes tracking

---

## Security Features

1. **Authentication**: Laravel Sanctum token-based auth
2. **Authorization**: Role and permission based
3. **Password Hashing**: Bcrypt with Laravel hashing
4. **API Rate Limiting**: Built-in throttle middleware
5. **CORS**: Cross-origin resource sharing configured
6. **SQL Injection**: Eloquent ORM protection
7. **XSS Protection**: Input sanitization
8. **Activity Logging**: All actions logged

---

## Flutter Integration

التطبيق جاهز للربط مع Flutter App. مثال على الاستخدام:

```dart
// Login
final response = await http.post(
  Uri.parse('http://your-api.com/api/auth/login'),
  headers: {'Content-Type': 'application/json'},
  body: jsonEncode({
    'email': 'student@edlibya.com',
    'password': 'password',
  }),
);

final data = jsonDecode(response.body);
String token = data['data']['access_token'];

// Use token in subsequent requests
final coursesResponse = await http.get(
  Uri.parse('http://your-api.com/api/courses'),
  headers: {
    'Authorization': 'Bearer $token',
  },
);
```

---

## Testing

```bash
# Run tests
php artisan test

# Run with coverage
php artisan test --coverage
```

---

## License

© 2025 EdLibya - All rights reserved

---

## Support

للدعم الفني، يرجى التواصل عبر:
- Email: support@edlibya.com
- Documentation: /docs
