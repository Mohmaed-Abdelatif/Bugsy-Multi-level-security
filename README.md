# What Bugsy
Bugsy is a PHP e-commerce REST API built on deliberate security tiers — V1, V2 — each one a complete, independently routable API version sharing the same database and core framework — sitting on top of the same business logic: products, categories, brands, carts, orders, user accounts, and reviews. The point of building it this way is not to ship a store. It's to make backend security visible by building the same feature twice: once the way an inexperienced developer would write it under deadline pressure, and once the way a security-conscious engineer would write it knowing what attackers actually do.

V1's job was never to be "bad code." It's realistic code — the kind that ships when a team is moving fast and security isn't yet a first-class concern. Every weakness in V1 is a real pattern seen in production systems: trusting a user_id field in a request body, hashing passwords with a fast general-purpose hash instead of a slow purpose-built one, building SQL with string interpolation because it's faster to write than parameter binding.
---

V1 — Vulnerable
Session auth, raw MySQLi queries, MD5 passwords, IDOR everywhere, no rate limiting. Built intentionally insecure as a teaching baseline.

V2 — Secured
JWT auth, PDO prepared statements, bcrypt, ownership checks, rate limiting, strict file upload validation, audit logging.
---
# Bugsy API - Reference
**Base URL:** `/api/Vnumber`
---

## Authentication
| Endpoint | Method | Body | Auth Required |
|----------|--------|------|---------------|
| `/register` | POST | `{name, email, password, phone, address}` | No |
| `/login` | POST | `{email, password}` | No |
| `/adminlogin` | POST | `{email, password}` | No |
| `/logout` | POST | - | Yes |
| `/password/forgot` | POST | `{email}` | No |
| `/password/reset` | POST | `{email, new_password}` | No |

---

## Admin add a new admin (only admin can do that)
| Endpoint | Method | Body | Auth Required |
|----------|--------|------|---------------|
| `/admin/add` | POST | `{name, email, password, phone}` | Admin |

---

## Products (Public)
| Endpoint | Method | Params | Auth Required |
|----------|--------|--------|---------------|
| `/products` | GET | `?page=1&per_page=20&category=1&brand=1&min_price=100&max_price=5000&sort=price&order=desc` | No |
| `/products/{id}` | GET | - | No |
| `/products/search` | GET | `?q=iphone&limit=20` | No |
| `/products/{id}/images` | GET | - | No |

---

## Products (Admin)
| Endpoint | Method | Content-Type | Body/Form | Auth Required |
|----------|--------|--------------|-----------|---------------|
| `/products` | POST | `multipart/form-data` | `name, description, price, stock, category_id, brand_id, main_image, additional_images[]` | Admin |
| `/products/{id}` | POST | `multipart/form-data` | `name, description, price, stock, main_image` (all optional) | Admin |
| `/products/{id}` | DELETE | - | - | Admin |
| `/products/{id}/images` | POST | `multipart/form-data` | `images[]` (max 5) | Admin |
| `/products/images/{id}` | DELETE | - | - | Admin |
| `/products/{id}/images/replace` | POST | `multipart/form-data` | `images[]` | Admin |

---

## Categories (Public)
| Endpoint | Method | Auth Required |
|----------|--------|---------------|
| `/categories` | GET | No |
| `/categories/{id}/products` | GET | No |

---

## Categories (Admin)
| Endpoint | Method | Content-Type | Body/Form | Auth Required |
|----------|--------|--------------|-----------|---------------|
| `/categories` | POST | `multipart/form-data` | `name, description, cat_image` | Admin |
| `/categories/{id}` | POST | `multipart/form-data` | `name, description, cat_image` (all optional) | Admin |
| `/categories/{id}` | DELETE | - | - | Admin |

---

## Brands (Public)
| Endpoint | Method | Auth Required |
|----------|--------|---------------|
| `/brands` | GET | No |
| `/brands/{id}/products` | GET | No |

---

## Brands (Admin)
| Endpoint | Method | Content-Type | Body/Form | Auth Required |
|----------|--------|--------------|-----------|---------------|
| `/brands` | POST | `multipart/form-data` | `name, logo` | Admin |
| `/brands/{id}` | POST | `multipart/form-data` | `name, logo` (all optional) | Admin |
| `/brands/{id}` | DELETE | - | - | Admin |

---

## Cart
| Endpoint | Method | Body | Auth Required |
|----------|--------|------|---------------|
| `/cart` | GET | - | Yes |
| `/cart/count` | GET | - | Yes |
| `/cart/total` | GET | - | Yes |
| `/cart/add` | POST | `{product_id, quantity}` | Yes |
| `/cart/items/{id}` | PUT | `{quantity}` | Yes |
| `/cart/items/{id}` | DELETE | - | Yes |
| `/cart/clear` | DELETE | - | Yes |
| `/cart/promo` | POST | `{promo_code}` | Yes |
| `/cart/clear` | DELETE | - | Yes |

---
---

## Promo Codes (Admin)
| Endpoint | Method | Body | Auth Required |
|----------|--------|------|---------------|
| `/promo-codes` | GET | - | Admin |
| `/promo-codes/{id}` | GET | - | Admin |
| `/promo-codes` | Post | `{code, discount_type, discount_value: percentage or fixed, min_order_amount, usage_limit_total, usage_limit_per_user, expires_at}` | Admin |
| `/promo-codes/{id}` | PUT | any field to change | Admin |
| `/promo-codes/{id}` | DELETE | `{quantity}` | Admin |

---

## Orders (Customer)
| Endpoint | Method | Body | Auth Required |
|----------|--------|------|---------------|
| `/checkout` | POST | `{payment_method, shipping_address, notes, card_details}` | Yes |
| `/orders` | GET | `?page=1&per_page=20` | Yes |
| `/orders/{id}` | GET | - | Yes |
| `/orders/{id}/items` | GET | - | Yes |
| `/orders/{id}/status` | GET | - | Yes |
| `/orders/{id}/cancel` | PUT | - | Yes |

**Payment Methods:** `cash`, `credit_card`, `debit_card`, `paypal`, `bank_transfer`

**Order Status:** `pending`, `processing`, `shipped`, `delivered`, `cancelled`

---

## Orders (Admin)
| Endpoint | Method | Body | Auth Required |
|----------|--------|------|---------------|
| `/orders` | GET | `?status=pending&user_id=5&page=1` | Admin |
| `/orders/{id}/status` | PUT | `{status}` | Admin |

---

## User Profile
| Endpoint | Method | Body | Auth Required |
|----------|--------|------|---------------|
| `/user/{id}` | GET | - | Yes |
| `/user/{id}` | PUT | `{name, phone, address}` | Yes |
| `/users/{id}/orders` | GET | - | Yes |
| `/users/{id}/password` | PUT | `{old_password, new_password}` | Yes |
| `/users/{id}/reviews` | GET | - | No |
| `/users/{id}/addresses` | GET | - | Yes |
| `/users/{id}/addresses` | POST | `{address}` | Yes |
| `/me` | GET | - | Yes |
| `/me` | PUT | - | Yes |
| `/users/{id}/photo` | POST | `multipart/form-data` => `photo` | Yes |
| `/users/{id}/photo` | GET | - | Yes |
| `/users/{id}/photo` | DELETE | - | Yes |



---

## User Management (Admin)
| Endpoint | Method | Auth Required |
|----------|--------|---------------|
| `/users` | GET | Admin |
| `/users/{id}` | DELETE | Admin |

---

## Reviews
| Endpoint | Method | Body | Auth Required |
|----------|--------|------|---------------|
| `/products/{id}/reviews` | GET | `?page=1&per_page=10&sort=recent` | No |
| `/products/{id}/rating` | GET | - | No |
| `/reviews/{id}` | GET | - | No |
| `/products/{id}/reviews` | POST | `{rating, title, comment}` | Yes |
| `/reviews/{id}` | PUT | `{rating, title, comment}` | Yes |
| `/reviews/{id}` | DELETE | - | Yes |
| `/reviews/{id}/helpful` | POST | - | No |
| `/users/{id}/reviews` | GET | - | No |

**Rating:** Float between 1.0 and 5.0

**Sort Options:** `recent`, `helpful`, `rating_high`, `rating_low`


---

## Testing Endpoints
| Endpoint | Method | Auth Required |
|----------|--------|---------------|
| `/test/public` | GET | No |
| `/test/protected` | GET | Yes |
| `/test/admin` | GET | Admin |
| `/test/session` | GET | Yes |
| `/test/ownership/{user_id}` | GET | Yes |

---
