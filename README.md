# 📋 Bugsy API - Quick Reference for v1 till now

**Base URL:** `http://20.174.36.199/api/v1`

---

## 🔐 Authentication
| Endpoint | Method | Body |
|----------|--------|------|
| `/register` | POST | `{name, email, password, phone, address}` |
| `/login` | POST | `{email, password}` |
| `/logout` | POST | - |
| `/password/forgot` | POST | `{email}` |
| `/password/reset` | POST | `{email, new_password}` |

---

## 🛍️ Products
| Endpoint | Method | Params |
|----------|--------|--------|
| `/products` | GET | `?page=1&per_page=20&category=1&sort=price&order=desc` |
| `/products/{id}` | GET | - |
| `/products/search` | GET | `?q=iphone&limit=20` |
| `/products/{id}/images` | GET | - |

---

## 📂 Categories & Brands
| Endpoint | Method |
|----------|--------|
| `/categories` | GET |
| `/categories/{id}/products` | GET |
| `/brands` | GET |
| `/brands/{id}/products` | GET |

---

## 🛒 Cart
| Endpoint | Method | Body |
|----------|--------|------|
| `/cart` | GET | - |
| `/cart/count` | GET | - |
| `/cart/total` | GET | - |
| `/cart/add` | POST | `{product_id, quantity}` |
| `/cart/items/{id}` | PUT | `{quantity}` |
| `/cart/items/{id}` | DELETE | - |
| `/cart/clear` | DELETE | - |

---

## 📦 Orders
| Endpoint | Method | Body |
|----------|--------|------|
| `/checkout` | POST | `{payment_method, shipping_address, notes}` |
| `/orders` | GET | `?page=1` |
| `/orders/{id}` | GET | - |
| `/orders/{id}/items` | GET | - |
| `/orders/{id}/status` | GET | - |
| `/orders/{id}/cancel` | PUT | - |

---

## 👤 User Profile
| Endpoint | Method | Body |
|----------|--------|------|
| `/user/{id}` | GET | - |
| `/user/{id}` | PUT | `{name, phone, address}` |
| `/users/{id}/orders` | GET | - |
| `/users/{id}/password` | PUT | `{old_password, new_password}` |
| `/users/{id}/reviews` | GET | - |

---

## ⭐ Reviews
| Endpoint | Method | Body |
|----------|--------|------|
| `/products/{id}/reviews` | GET | `?page=1&sort=recent` |
| `/products/{id}/rating` | GET | - |
| `/reviews/{id}` | GET | - |
| `/products/{id}/reviews` | POST | `{rating, title, comment}` |
| `/reviews/{id}` | PUT | `{rating, title, comment}` |
| `/reviews/{id}` | DELETE | - |
| `/reviews/{id}/helpful` | POST | - |

---

## 🎨 Response Format

### Success
```json
{
  "success": true,
  "message": "...",
  "data": {...}
}
```

### Error
```json
{
  "success": false,
  "error": "Error Type",
  "message": "Error description",
  "errors": {}
}
```

---

## 🔑 Key Headers
```javascript
{
  'Content-Type': 'application/json',
  'credentials': 'include' // For session cookies
}
```

---

## 📱 Quick Fetch Example
```javascript
const API = 'http://20.174.36.199/api/v1';

// GET with session
fetch(`${API}/products`, {
  credentials: 'include'
});

// POST with body
fetch(`${API}/cart/add`, {
  method: 'POST',
  credentials: 'include',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ product_id: 1, quantity: 2 })
});
```

---

## 🚨 Common Status Codes
- `200` OK
- `201` Created
- `400` Bad Request
- `401` Unauthorized
- `404` Not Found
- `422` Validation Failed
- `500` Server Error
