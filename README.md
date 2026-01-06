# 📋 Bugsy API - Quick Reference for v1 till now

**Base URL:** `http://20.174.36.199/api/v1`

---

## 🔐 Authentication
| Endpoint | Method | Body | Auth Required |
|----------|--------|------|---------------|
| `/register` | POST | `{name, email, password, phone, address}` | No |
| `/login` | POST | `{email, password}` | No |
| `/adminlogin` | POST | `{email, password}` | No |
| `/logout` | POST | - | Yes |
| `/password/forgot` | POST | `{email}` | No |
| `/password/reset` | POST | `{email, new_password}` | No |

---

## 🔒 Admin add a new admin (only admin can do that)
| Endpoint | Method | Body | Auth Required |
|----------|--------|------|---------------|
| `/admin/add` | POST | `{name, email, password, phone}` | Admin |

---

## 🛍️ Products (Public)
| Endpoint | Method | Params | Auth Required |
|----------|--------|--------|---------------|
| `/products` | GET | `?page=1&per_page=20&category=1&brand=1&min_price=100&max_price=5000&sort=price&order=desc` | No |
| `/products/{id}` | GET | - | No |
| `/products/search` | GET | `?q=iphone&limit=20` | No |
| `/products/{id}/images` | GET | - | No |

---

## 🛠️ Products (Admin)
| Endpoint | Method | Content-Type | Body/Form | Auth Required |
|----------|--------|--------------|-----------|---------------|
| `/products` | POST | `multipart/form-data` | `name, description, price, stock, category_id, brand_id, main_image, additional_images[]` | Admin |
| `/products/{id}` | POST | `multipart/form-data` | `name, description, price, stock, main_image` (all optional) | Admin |
| `/products/{id}` | DELETE | - | - | Admin |
| `/products/{id}/images` | POST | `multipart/form-data` | `images[]` (max 5) | Admin |
| `/products/images/{id}` | DELETE | - | - | Admin |
| `/products/{id}/images/replace` | POST | `multipart/form-data` | `images[]` | Admin |

---

## 📂 Categories (Public)
| Endpoint | Method | Auth Required |
|----------|--------|---------------|
| `/categories` | GET | No |
| `/categories/{id}/products` | GET | No |

---

## 📂 Categories (Admin)
| Endpoint | Method | Content-Type | Body/Form | Auth Required |
|----------|--------|--------------|-----------|---------------|
| `/categories` | POST | `multipart/form-data` | `name, description, cat_image` | Admin |
| `/categories/{id}` | POST | `multipart/form-data` | `name, description, cat_image` (all optional) | Admin |
| `/categories/{id}` | DELETE | - | - | Admin |

---

## 🏷️ Brands (Public)
| Endpoint | Method | Auth Required |
|----------|--------|---------------|
| `/brands` | GET | No |
| `/brands/{id}/products` | GET | No |

---

## 🏷️ Brands (Admin)
| Endpoint | Method | Content-Type | Body/Form | Auth Required |
|----------|--------|--------------|-----------|---------------|
| `/brands` | POST | `multipart/form-data` | `name, logo` | Admin |
| `/brands/{id}` | POST | `multipart/form-data` | `name, logo` (all optional) | Admin |
| `/brands/{id}` | DELETE | - | - | Admin |

---

## 🛒 Cart
| Endpoint | Method | Body | Auth Required |
|----------|--------|------|---------------|
| `/cart` | GET | - | Session |
| `/cart/count` | GET | - | Session |
| `/cart/total` | GET | - | Session |
| `/cart/add` | POST | `{product_id, quantity}` | Session |
| `/cart/items/{id}` | PUT | `{quantity}` | Session |
| `/cart/items/{id}` | DELETE | - | Session |
| `/cart/clear` | DELETE | - | Session |

---

## 📦 Orders (Customer)
| Endpoint | Method | Body | Auth Required |
|----------|--------|------|---------------|
| `/checkout` | POST | `{payment_method, shipping_address, notes, card_details}` | Session |
| `/orders` | GET | `?page=1&per_page=20` | Session |
| `/orders/{id}` | GET | - | Session |
| `/orders/{id}/items` | GET | - | Session |
| `/orders/{id}/status` | GET | - | Session |
| `/orders/{id}/cancel` | PUT | - | Session |

**Payment Methods:** `cash`, `credit_card`, `debit_card`, `paypal`, `bank_transfer`

**Order Status:** `pending`, `processing`, `shipped`, `delivered`, `cancelled`

---

## 📦 Orders (Admin)
| Endpoint | Method | Body | Auth Required |
|----------|--------|------|---------------|
| `/orders` | GET | `?status=pending&user_id=5&page=1` | Admin |
| `/orders/{id}/status` | PUT | `{status}` | Admin |

---

## 👤 User Profile
| Endpoint | Method | Body | Auth Required |
|----------|--------|------|---------------|
| `/user/{id}` | GET | - | Session |
| `/user/{id}` | PUT | `{name, phone, address}` | Session |
| `/users/{id}/orders` | GET | - | Session |
| `/users/{id}/password` | PUT | `{old_password, new_password}` | Session |
| `/users/{id}/reviews` | GET | - | No |
| `/users/{id}/addresses` | GET | - | Session |
| `/users/{id}/addresses` | POST | `{address}` | Session |
| `/me` | GET | - | Session |
| `/users/{id}/photo` | POST | - | Session |
| `/users/{id}/photo` | GET | - | Session |
| `/users/{id}/photo` | DELETE | - | Session |



---

## 👥 User Management (Admin)
| Endpoint | Method | Auth Required |
|----------|--------|---------------|
| `/users` | GET | Admin |
| `/users/{id}` | DELETE | Admin |

---

## ⭐ Reviews
| Endpoint | Method | Body | Auth Required |
|----------|--------|------|---------------|
| `/products/{id}/reviews` | GET | `?page=1&per_page=10&sort=recent` | No |
| `/products/{id}/rating` | GET | - | No |
| `/reviews/{id}` | GET | - | No |
| `/products/{id}/reviews` | POST | `{rating, title, comment}` | Session |
| `/reviews/{id}` | PUT | `{rating, title, comment}` | Session |
| `/reviews/{id}` | DELETE | - | Session |
| `/reviews/{id}/helpful` | POST | - | No |
| `/users/{id}/reviews` | GET | - | No |

**Rating:** Float between 1.0 and 5.0

**Sort Options:** `recent`, `helpful`, `rating_high`, `rating_low`

---

## 🔍 Search (Coming Soon)
| Endpoint | Method | Params |
|----------|--------|--------|
| `/search` | GET | `?q=query` |
| `/search/suggestions` | GET | `?q=query` |
| `/search/trending` | GET | - |

---

## 🧪 Testing Endpoints
| Endpoint | Method | Auth Required |
|----------|--------|---------------|
| `/test/public` | GET | No |
| `/test/protected` | GET | Session |
| `/test/admin` | GET | Admin |
| `/test/session` | GET | Session |
| `/test/ownership/{user_id}` | GET | Session |

---
