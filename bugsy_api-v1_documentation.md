# 📚 Bugsy API Documentation v1.0

## 🌐 Base URL
```
http://20.174.36.199/api/v1
```

## 📋 Table of Contents
1. [Authentication](#authentication)
2. [Products](#products)
3. [Categories](#categories)
4. [Brands](#brands)
5. [Cart](#cart)
6. [Orders](#orders)
7. [User Profile](#user-profile)
8. [Reviews](#reviews)
9. [Error Handling](#error-handling)

---

## 🔐 Authentication

### Register New User
**Endpoint:** `POST /register`

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "phone": "01012345678",
  "address": "123 Main St, Cairo"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Registration successful",
  "data": {
    "user": {
      "id": 5,
      "name": "John Doe",
      "email": "john@example.com",
      "role": "customer",
      "is_active": true
    },
    "session_id": "abc123xyz..."
  }
}
```

---

### Login
**Endpoint:** `POST /login`

**Request Body:**
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 5,
      "name": "John Doe",
      "email": "john@example.com",
      "phone": "01012345678",
      "address": "123 Main St, Cairo",
      "role": "customer",
      "is_active": true,
      "created_at": "2025-01-20 10:30:00"
    },
    "session_id": "abc123xyz..."
  }
}
```

**⚠️ Important:** Store `session_id` in cookies for subsequent requests.

---

### Logout
**Endpoint:** `POST /logout`

**Response (200):**
```json
{
  "success": true,
  "message": "Logout successful"
}
```

---

### Forgot Password
**Endpoint:** `POST /password/forgot`

**Request Body:**
```json
{
  "email": "john@example.com"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Password reset information retrieved",
  "data": {
    "email": "john@example.com",
    "user_id": 5
  }
}
```

---

### Reset Password
**Endpoint:** `POST /password/reset`

**Request Body:**
```json
{
  "email": "john@example.com",
  "new_password": "newpassword123"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Password reset successfully"
}
```

---

## 🛍️ Products

### Get All Products
**Endpoint:** `GET /products`

**Query Parameters:**
- `page` (optional): Page number (default: 1)
- `per_page` (optional): Items per page (default: 20, max: 100)
- `category` (optional): Filter by category ID
- `brand` (optional): Filter by brand ID
- `min_price` (optional): Minimum price
- `max_price` (optional): Maximum price
- `sort` (optional): Sort field (`price`, `rating`, `created_at`, `name`)
- `order` (optional): Sort order (`asc`, `desc`)

**Example:**
```
GET /products?page=1&per_page=20&category=1&sort=price&order=desc
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "products": [
      {
        "id": 1,
        "name": "iPhone 15 Pro Max",
        "description": "Latest flagship iPhone",
        "price": 48999.00,
        "stock": 50,
        "rating": 4.5,
        "main_image": "product_123.jpg",
        "main_image_url": "http://20.174.36.199/uploads/products/product_123.jpg",
        "category_id": 1,
        "category_name": "Phones",
        "brand_id": 2,
        "brand_name": "Apple",
        "is_available": true,
        "created_at": "2025-01-15 10:30:00"
      }
    ],
    "pagination": {
      "total": 150,
      "perPage": 20,
      "page": 1,
      "totalPages": 8
    }
  }
}
```

---

### Get Single Product
**Endpoint:** `GET /products/{id}`

**Example:** `GET /products/1`

**Response (200):**
```json
{
  "success": true,
  "data": {
    "product": {
      "id": 1,
      "name": "iPhone 15 Pro Max",
      "description": "Latest flagship iPhone with A17 Pro chip",
      "price": 48999.00,
      "stock": 50,
      "rating": 4.5,
      "main_image": "product_123.jpg",
      "main_image_url": "http://20.174.36.199/uploads/products/product_123.jpg",
      "category_id": 1,
      "category_name": "Phones",
      "brand_id": 2,
      "brand_name": "Apple",
      "specifications": {
        "screen": "6.7 inch",
        "ram": "8GB",
        "storage": "256GB"
      },
      "is_available": true,
      "created_at": "2025-01-15 10:30:00"
    }
  }
}
```

---

### Search Products
**Endpoint:** `GET /products/search`

**Query Parameters:**
- `q`: Search keyword (required)
- `limit`: Results limit (optional, default: 20, max: 100)

**Example:** `GET /products/search?q=iphone&limit=10`

**Response (200):**
```json
{
  "success": true,
  "data": {
    "results": [
      {
        "id": 1,
        "name": "iPhone 15 Pro Max",
        "price": 48999.00,
        "main_image_url": "http://20.174.36.199/uploads/products/product_123.jpg",
        "rating": 4.5
      }
    ],
    "keyword": "iphone",
    "total": 5
  }
}
```

---

### Get Product Images
**Endpoint:** `GET /products/{id}/images`

**Response (200):**
```json
{
  "success": true,
  "data": {
    "product_id": 1,
    "images": [
      {
        "id": 10,
        "filename": "product_123_1.jpg",
        "url": "http://20.174.36.199/uploads/products/product_123_1.jpg",
        "created_at": "2025-01-20 10:30:00"
      },
      {
        "id": 11,
        "filename": "product_123_2.jpg",
        "url": "http://20.174.36.199/uploads/products/product_123_2.jpg",
        "created_at": "2025-01-20 10:31:00"
      }
    ],
    "count": 2
  }
}
```

---

## 📂 Categories

### Get All Categories
**Endpoint:** `GET /categories`

**Response (200):**
```json
{
  "success": true,
  "data": {
    "categories": [
      {
        "id": 1,
        "name": "Phones",
        "description": "Mobile phones and smartphones",
        "cat_image": "category_1.jpg",
        "cat_image_url": "http://20.174.36.199/uploads/products/category_1.jpg",
        "product_count": 45,
        "created_at": "2025-01-10 10:00:00"
      },
      {
        "id": 2,
        "name": "Tablets",
        "description": "Tablets and iPads",
        "cat_image": "category_2.jpg",
        "cat_image_url": "http://20.174.36.199/uploads/products/category_2.jpg",
        "product_count": 23,
        "created_at": "2025-01-10 10:01:00"
      }
    ],
    "total": 5
  }
}
```

---

### Get Products by Category
**Endpoint:** `GET /categories/{id}/products`

**Query Parameters:**
- `page` (optional): Page number
- `per_page` (optional): Items per page

**Example:** `GET /categories/1/products?page=1&per_page=20`

**Response (200):**
```json
{
  "success": true,
  "data": {
    "products": [...],
    "category_id": 1,
    "pagination": {
      "total": 45,
      "perPage": 20,
      "page": 1,
      "totalPages": 3
    }
  }
}
```

---

## 🏷️ Brands

### Get All Brands
**Endpoint:** `GET /brands`

**Response (200):**
```json
{
  "success": true,
  "data": {
    "brands": [
      {
        "id": 1,
        "name": "Apple",
        "logo": "apple_logo.png",
        "logo_url": "http://20.174.36.199/uploads/products/apple_logo.png",
        "product_count": 30,
        "created_at": "2025-01-05 10:00:00"
      },
      {
        "id": 2,
        "name": "Samsung",
        "logo": "samsung_logo.png",
        "logo_url": "http://20.174.36.199/uploads/products/samsung_logo.png",
        "product_count": 40,
        "created_at": "2025-01-05 10:01:00"
      }
    ],
    "total": 10
  }
}
```

---

### Get Products by Brand
**Endpoint:** `GET /brands/{id}/products`

**Query Parameters:**
- `page` (optional): Page number
- `per_page` (optional): Items per page

**Example:** `GET /brands/1/products?page=1`

**Response:** Same structure as products list

---

## 🛒 Cart

### Get User Cart
**Endpoint:** `GET /cart`

**Authentication:** Required (session-based)

**Response (200):**
```json
{
  "success": true,
  "data": {
    "cart": {
      "id": 1,
      "user_id": 5,
      "items": [
        {
          "id": 10,
          "cart_id": 1,
          "product_id": 1,
          "product_name": "iPhone 15 Pro Max",
          "product_price": 48999.00,
          "product_image": "product_123.jpg",
          "product_stock": 50,
          "quantity": 2,
          "price": 48999.00,
          "subtotal": 97998.00
        }
      ],
      "total": 97998.00,
      "item_count": 2,
      "created_at": "2025-01-20 10:00:00"
    }
  }
}
```

---

### Get Cart Item Count
**Endpoint:** `GET /cart/count`

**Authentication:** Required

**Response (200):**
```json
{
  "success": true,
  "data": {
    "count": 3
  }
}
```

---

### Get Cart Total
**Endpoint:** `GET /cart/total`

**Authentication:** Required

**Response (200):**
```json
{
  "success": true,
  "data": {
    "total": 97998.00,
    "item_count": 2
  }
}
```

---

### Add Item to Cart
**Endpoint:** `POST /cart/add`

**Request Body:**
```json
{
  "product_id": 1,
  "quantity": 2
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Item added to cart",
  "data": {
    "cart_item": {
      "id": 15,
      "product_id": 1,
      "quantity": 2,
      "subtotal": 97998.00
    }
  }
}
```

---

### Update Cart Item Quantity
**Endpoint:** `PUT /cart/items/{id}`

**Request Body:**
```json
{
  "quantity": 3
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Cart item updated",
  "data": {
    "item": {
      "id": 15,
      "quantity": 3,
      "subtotal": 146997.00
    }
  }
}
```

---

### Remove Item from Cart
**Endpoint:** `DELETE /cart/items/{id}`

**Response (200):**
```json
{
  "success": true,
  "message": "Item removed from cart"
}
```

---

### Clear Cart
**Endpoint:** `DELETE /cart/clear`

**Response (200):**
```json
{
  "success": true,
  "message": "Cart cleared successfully"
}
```

---

## 📦 Orders

### Checkout (Create Order)
**Endpoint:** `POST /checkout`

**Request Body:**
```json
{
  "payment_method": "cash",
  "shipping_address": "123 Main St, Apartment 4B, Cairo, Egypt",
  "notes": "Please call before delivery",
  "card_details": {
    "card_number": "4242424242424242",
    "cvv": "123",
    "expiry": "12/25"
  }
}
```

**Payment Methods:**
- `cash` - Cash on delivery
- `credit_card` - Credit card
- `debit_card` - Debit card
- `paypal` - PayPal
- `bank_transfer` - Bank transfer

**Response (201):**
```json
{
  "success": true,
  "message": "Order placed successfully",
  "data": {
    "order": {
      "id": 42,
      "order_number": "ORD-20250120-00042",
      "user_id": 5,
      "total": 97998.00,
      "status": "processing",
      "payment_status": "paid",
      "payment_method": "cash",
      "shipping_address": "123 Main St, Cairo",
      "items": [
        {
          "product_id": 1,
          "product_name": "iPhone 15 Pro Max",
          "quantity": 2,
          "price": 48999.00,
          "subtotal": 97998.00
        }
      ],
      "created_at": "2025-01-20 14:30:00"
    },
    "payment": {
      "status": "paid",
      "method": "cash",
      "transaction_id": "CASH-1705750123"
    }
  }
}
```

---

### Get User Orders
**Endpoint:** `GET /orders`

**Authentication:** Required

**Query Parameters:**
- `page` (optional): Page number
- `per_page` (optional): Items per page

**Response (200):**
```json
{
  "success": true,
  "data": {
    "orders": [
      {
        "id": 42,
        "order_number": "ORD-20250120-00042",
        "total": 97998.00,
        "status": "processing",
        "payment_status": "paid",
        "payment_method": "cash",
        "created_at": "2025-01-20 14:30:00"
      }
    ],
    "pagination": {
      "total": 10,
      "perPage": 20,
      "page": 1,
      "totalPages": 1
    }
  }
}
```

**Order Status Values:**
- `pending` - Order placed, awaiting processing
- `processing` - Order being prepared
- `shipped` - Order shipped
- `delivered` - Order delivered
- `cancelled` - Order cancelled

---

### Get Single Order
**Endpoint:** `GET /orders/{id}`

**Response (200):**
```json
{
  "success": true,
  "data": {
    "order": {
      "id": 42,
      "order_number": "ORD-20250120-00042",
      "user_id": 5,
      "total": 97998.00,
      "status": "processing",
      "payment_status": "paid",
      "payment_method": "cash",
      "shipping_address": "123 Main St, Cairo",
      "notes": "Please call before delivery",
      "items": [
        {
          "id": 100,
          "product_id": 1,
          "product_name": "iPhone 15 Pro Max",
          "quantity": 2,
          "price": 48999.00,
          "subtotal": 97998.00,
          "product_image": "product_123.jpg"
        }
      ],
      "item_count": 2,
      "created_at": "2025-01-20 14:30:00",
      "updated_at": "2025-01-20 14:30:00"
    }
  }
}
```

---

### Get Order Items
**Endpoint:** `GET /orders/{id}/items`

**Response (200):**
```json
{
  "success": true,
  "data": {
    "items": [
      {
        "id": 100,
        "product_id": 1,
        "product_name": "iPhone 15 Pro Max",
        "quantity": 2,
        "price": 48999.00,
        "subtotal": 97998.00,
        "product_image": "product_123.jpg",
        "product_available": true
      }
    ],
    "total": 97998.00
  }
}
```

---

### Get Order Status
**Endpoint:** `GET /orders/{id}/status`

**Response (200):**
```json
{
  "success": true,
  "data": {
    "order_id": 42,
    "order_number": "ORD-20250120-00042",
    "status": "processing",
    "payment_status": "paid",
    "created_at": "2025-01-20 14:30:00",
    "updated_at": "2025-01-20 14:35:00"
  }
}
```

---

### Cancel Order
**Endpoint:** `PUT /orders/{id}/cancel`

**Response (200):**
```json
{
  "success": true,
  "message": "Order cancelled successfully",
  "data": {
    "order_id": 42,
    "refund_status": "refunded"
  }
}
```

**Note:** Orders can only be cancelled if status is `pending` or `processing`.

---

## 👤 User Profile

### Get User Profile
**Endpoint:** `GET /user/{id}`

**Response (200):**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 5,
      "name": "John Doe",
      "email": "john@example.com",
      "phone": "01012345678",
      "address": "123 Main St, Cairo",
      "role": "customer",
      "is_active": true,
      "created_at": "2025-01-15 10:00:00"
    }
  }
}
```

---

### Update User Profile
**Endpoint:** `PUT /user/{id}`

**Request Body:**
```json
{
  "name": "John Updated",
  "phone": "01098765432",
  "address": "456 New St, Cairo"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Profile updated successfully",
  "data": {
    "user": {
      "id": 5,
      "name": "John Updated",
      "email": "john@example.com",
      "phone": "01098765432",
      "address": "456 New St, Cairo"
    }
  }
}
```

---

### Get User Orders
**Endpoint:** `GET /users/{id}/orders`

**Response:** Same as GET /orders

---

### Change Password
**Endpoint:** `PUT /users/{id}/password`

**Request Body:**
```json
{
  "old_password": "oldpass123",
  "new_password": "newpass456"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Password changed successfully"
}
```

---

## ⭐ Reviews

### Get Product Reviews
**Endpoint:** `GET /products/{id}/reviews`

**Query Parameters:**
- `page` (optional): Page number
- `per_page` (optional): Items per page (default: 10)
- `sort` (optional): `recent`, `helpful`, `rating_high`, `rating_low`

**Response (200):**
```json
{
  "success": true,
  "data": {
    "reviews": [
      {
        "id": 1,
        "product_id": 1,
        "user_id": 5,
        "user_name": "John Doe",
        "rating": 4.5,
        "title": "Great phone!",
        "comment": "Very satisfied with this purchase",
        "is_verified_purchase": true,
        "helpful_count": 15,
        "created_at": "2025-01-18 10:00:00"
      }
    ],
    "pagination": {
      "total": 50,
      "perPage": 10,
      "page": 1,
      "totalPages": 5
    },
    "stats": {
      "total_reviews": 50,
      "median_rating": 4.5,
      "average_rating": 4.3,
      "distribution": {
        "5_star": 30,
        "4_star": 15,
        "3_star": 3,
        "2_star": 1,
        "1_star": 1
      }
    }
  }
}
```

---

### Get Product Rating Summary
**Endpoint:** `GET /products/{id}/rating`

**Response (200):**
```json
{
  "success": true,
  "data": {
    "product_id": 1,
    "median_rating": 4.5,
    "average_rating": 4.3,
    "total_reviews": 50,
    "distribution": {
      "5_star": 30,
      "4_star": 15,
      "3_star": 3,
      "2_star": 1,
      "1_star": 1
    }
  }
}
```

---

### Get Single Review
**Endpoint:** `GET /reviews/{id}`

**Response (200):**
```json
{
  "success": true,
  "data": {
    "review": {
      "id": 1,
      "product_id": 1,
      "user_id": 5,
      "user_name": "John Doe",
      "rating": 4.5,
      "title": "Great phone!",
      "comment": "Very satisfied...",
      "is_verified_purchase": true,
      "helpful_count": 15,
      "created_at": "2025-01-18 10:00:00"
    }
  }
}
```

---

### Create Review
**Endpoint:** `POST /products/{id}/reviews`

**Request Body:**
```json
{
  "rating": 4.5,
  "title": "Great phone!",
  "comment": "Very satisfied with this purchase. Battery life is excellent."
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Review created successfully",
  "data": {
    "review": {
      "id": 55,
      "product_id": 1,
      "user_id": 5,
      "rating": 4.5,
      "title": "Great phone!",
      "is_verified_purchase": true,
      "created_at": "2025-01-20 15:00:00"
    }
  }
}
```

---

### Update Review
**Endpoint:** `PUT /reviews/{id}`

**Request Body:**
```json
{
  "rating": 5.0,
  "title": "Updated: Perfect phone!",
  "comment": "Changed my mind - it's amazing!"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Review updated successfully",
  "data": {
    "review": {...}
  }
}
```

---

### Delete Review
**Endpoint:** `DELETE /reviews/{id}`

**Response (200):**
```json
{
  "success": true,
  "message": "Review deleted successfully"
}
```

---

### Mark Review as Helpful
**Endpoint:** `POST /reviews/{id}/helpful`

**Response (200):**
```json
{
  "success": true,
  "message": "Review marked as helpful",
  "data": {
    "helpful_count": 16
  }
}
```

---

### Get User's Reviews
**Endpoint:** `GET /users/{id}/reviews`

**Response (200):**
```json
{
  "success": true,
  "data": {
    "reviews": [
      {
        "id": 1,
        "product_id": 1,
        "product_name": "iPhone 15 Pro Max",
        "product_image": "product_123.jpg",
        "rating": 4.5,
        "title": "Great phone!",
        "created_at": "2025-01-18 10:00:00"
      }
    ],
    "total": 5
  }
}
```

---

## ❌ Error Handling

### Error Response Format
All errors follow this structure:

```json
{
  "success": false,
  "error": "Error Type",
  "message": "Human-readable error message",
  "errors": {
    "field_name": "Specific field error"
  }
}
```

### Common HTTP Status Codes

| Code | Meaning | Example |
|------|---------|---------|
| 200 | OK | Request successful |
| 201 | Created | Resource created (order, review) |
| 400 | Bad Request | Invalid input data |
| 401 | Unauthorized | Not logged in |
| 403 | Forbidden | Insufficient permissions |
| 404 | Not Found | Resource doesn't exist |
| 409 | Conflict | Duplicate entry (email exists) |
| 422 | Validation Failed | Input validation errors |
| 500 | Server Error | Internal server error |

### Example Error Responses

**Validation Error (422):**
```json
{
  "success": false,
  "error": "Validation Failed",
  "message": "Validation failed",
  "errors": {
    "email": "Email is required",
    "password": "Password must be at least 4 characters"
  }
}
```

**Not Found (404):**
```json
{
  "success": false,
  "error": "Not Found",
  "message": "Product not found"
}
```

**Unauthorized (401):**
```json
{
  "success": false,
  "error": "Unauthorized",
  "message": "Please login to continue"
}
```

---

## 🔧 Technical Notes

### Authentication
- V1 uses **session-based authentication**
- Session cookie is automatically managed by the browser
- Include credentials in requests: `credentials: 'include'`

### CORS
- API supports CORS for: `https://gp-mobile-ecommerce.vercel.app`
- Local development: `http://localhost:*` ports allowed

### Rate Limiting
- V1: No rate limiting (intentionally vulnerable)
- V2/V3: Will implement rate limiting

### Image URLs
All image URLs are absolute:
```
http://20.174.36.199/uploads/products/filename.jpg
```

### Pagination
Default pagination:
- Default page: 1
- Default per_page: 20
- Max per_page: 100

---

## 📱 Frontend Integration Examples

### JavaScript/Fetch
```javascript
const API_BASE = 'http://20.174.36.199/api/v1';

// Login
async function login(email, password) {
  const response = await fetch(`${API_BASE}/login`, {
    method: 'POST',
    credentials: 'include', // Important for sessions
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ email, password })
  });
  
  return await response.json();
}

// Get products
async function getProducts(page = 1) {
  const response = await fetch(`${API_BASE}/products?page=${page}`, {
    credentials: 'include'
  });
  
  return await response.json();
}

// Add to cart
async function addToCart(productId, quantity) {
  const response = await fetch(`${API_BASE}/cart/add`, {
    method: 'POST',
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ product_id: productId, quantity })
  });
  
  return await response.json();
}
```

### React/Axios
```javascript
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://20.174.36.199/api/v1',
  withCredentials: true
});

// Login
export const login = (email, password) => {
  return api.post('/login', { email, password });
};

// Get products
export const getProducts = (params) => {
  return api.get('/products', { params });
};

// Add to cart
export const addToCart = (productId, quantity) => {
  return api.post('/cart/add', { product_id: productId, quantity });
};
```

---

## 🆘 Support

For issues or questions:
- Check error logs in browser console
- Verify request format matches documentation
- Ensure session cookie is being sent
- Contact backend team for server issues

---

## 📌 Version History

- **v1.0** (2025-01-20): Initial release
  - Basic CRUD operations
  - Session-based authentication
  - V1 security level (intentionally vulnerable)

---

## 🔜 Upcoming (V2/V3)

- JWT authentication
- Rate limiting
- Enhanced security
- 2FA support
- Advanced features

---

**Base URL:** `http://20.174.36.199/api/v1`

**Last Updated:** January 20, 2025
