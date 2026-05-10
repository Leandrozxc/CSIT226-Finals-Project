# 🎫 University Helpdesk System

A web-based helpdesk ticketing system built with **PHP**, **MySQL**, and **XAMPP** for managing university support requests across departments.

***

## 📋 Features

- **Role-based access** — Admin, Staff, Student, Faculty, Employee
- **Ticket management** — Submit, track, assign, resolve, and close tickets
- **Priority levels** — Low, Normal, High, Urgent (with urgent reason validation)
- **Department routing** — Tickets assigned to specific university departments
- **Follow-up messaging** — Chat-style communication between requester and staff
- **Audit history log** — Every status change is tracked with timestamps
- **Admin dashboard** — Stats overview, department breakdown, recent tickets
- **Reports page** — Ticket analytics by status, priority, and department
- **Duplicate detection** — Warns if a similar ticket was submitted within 24 hours

***

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.x |
| Database | MySQL 8.x via phpMyAdmin |
| Server | Apache (XAMPP) |
| Frontend | HTML5, CSS3, Vanilla JavaScript |
| Fonts | Google Fonts (Inter + DM Serif Display) |

***

## 📁 Project Structure

```
csit226finals/
├── admin/
│   ├── dashboard.php       # Admin overview & stats
│   ├── tickets.php         # All tickets with filters
│   ├── ticket_detail.php   # Manage individual ticket
│   ├── users.php           # User management
│   ├── departments.php     # Department management
│   └── reports.php         # Analytics & reports
├── student/
│   ├── dashboard.php       # Student overview
│   ├── new_ticket.php      # Submit new ticket
│   ├── my_tickets.php      # View own tickets
│   ├── ticket_view.php     # View ticket details
│   └── ticket_history.php  # Ticket history log
├── includes/
│   ├── db.php              # Database connection
│   ├── auth.php            # Authentication & helpers
│   ├── sidebar_admin.php   # Admin sidebar nav
│   └── sidebar_student.php # Student sidebar nav
├── assets/
│   └── style.css           # Global stylesheet
├── index.php               # Login page
├── logout.php              # Logout handler
└── csit226finals.sql       # Database schema + seed data
```

***

## ⚙️ Installation

### Requirements
- XAMPP (Apache + MySQL + PHP 8.x)
- Web browser

### Steps

**1. Clone or download the repository**
```bash
git clone https://github.com/yourusername/csit226finals.git
```

**2. Move to XAMPP htdocs**
```
C:\xampp\htdocs\csit226finals\
```

**3. Import the database**
- Open `http://localhost/phpmyadmin`
- Click **Import** tab
- Select `csit226finals.sql`
- Click **Go**

**4. Run the project**
```
http://localhost/csit226finals/
```

***

## 👤 Demo Accounts

| Role | Email | Password |
|---|---|---|
| Admin | admin@university.edu | password |
| Staff | staff@university.edu | password |
| Student | student@university.edu | password |
| Faculty | faculty@university.edu | password |
| Employee | employee@university.edu | password |

***

## 🗄️ Database Tables

| Table | Description |
|---|---|
| `departments` | University departments |
| `users` | All system users |
| `tickets` | Support tickets |
| `followups` | Messages per ticket |
| `history_log` | Audit trail of ticket changes |

***

## 📌 Business Rules

1. Only authenticated users can submit tickets
2. Admin and Staff can manage and update all tickets
3. Students, Faculty, and Employees can only view their own tickets
4. Urgent tickets require a written justification reason
5. Duplicate ticket warning triggers if same category submitted within 24 hours
6. Tickets cannot be edited after being marked Closed

***

## 📄 License

This project was created for academic purposes — **CSIT226 Final Project**.
