# University Helpdesk System
**CSIT226 - Information Management | Final Project**
Pabatao, Leandro Miguel A. | Racaza, Psalm Elly

A web-based helpdesk ticketing platform built with PHP, MySQL, and XAMPP that centralizes all university support requests into one system with real-time status tracking, department routing, and admin reporting.

---

## Table of Contents
1. [Project Overview](#project-overview)
2. [Features](#features)
3. [Tech Stack](#tech-stack)
4. [Project Structure](#project-structure)
5. [Installation](#installation)
6. [Demo Accounts](#demo-accounts)
7. [Database Design](#database-design)
8. [CRUD Implementation](#crud-implementation)
9. [Business Rules](#business-rules)
10. [Code Explanation](#code-explanation)

---

## Project Overview

### Identified Pain Point
The university lacks a centralized communication and tracking system for student concerns. Students are confused about where to file specific reports such as IT, Registrar, or Facilities, leading to constant manual follow-ups without status updates. Administrators are overwhelmed by requests coming from scattered channels such as walk-ins, chat, and email, resulting in lost tickets and an inability to prioritize urgent tasks.

### Proposed Solution
The University Services Helpdesk is a centralized web-based ticketing platform where all student requests are consolidated into a single database. The system allows for automated categorization, department assignment, and real-time status tracking, providing transparency for students and data-driven reporting for administrators.

### Target Users
- **Students** - the primary requesters who file tickets and track their progress
- **Staff/Encoders** - personnel who can file tickets on behalf of students and resolve assigned tasks
- **Admins/Department Heads** - users who categorize tickets, assign them to staff, manage statuses, and generate performance reports

---

## Features

### Student Portal
- Login with email and password
- Submit new support tickets with category, priority, department, description, location, and file attachment
- Receive a unique Ticket Number immediately after submission
- View all personal tickets with status indicators
- Send follow-up messages to admin or staff in a chat-style interface
- View the full ticket history audit log
- Duplicate ticket warning when the same category is submitted within 24 hours
- Urgent priority requires a written justification

### Admin Panel
- Dashboard with live statistics for Open, In Progress, Resolved, Closed, and Urgent tickets
- Department breakdown with visual progress bars
- View and filter all tickets by status, priority, department, or keyword
- Full ticket management: assign staff, change status, add update notes, and write resolution summaries
- Reply to ticket requesters in a chat-style interface
- Complete audit history log per ticket
- Closed tickets are fully read-only
- User management: add, activate or deactivate, and delete users
- Department management: add and activate or deactivate departments
- Reports and analytics: ticket breakdown by status, priority, department, and category

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.x |
| Database | MySQL 8.x |
| Database UI | phpMyAdmin via XAMPP |
| Web Server | Apache via XAMPP |
| Frontend | HTML5, CSS3, Vanilla JavaScript |
| Fonts | Google Fonts - Inter and DM Serif Display |
| Styling | Custom CSS with no external frameworks |

---

## Project Structure

```
csit226finals/
|-- admin/
|   |-- dashboard.php       # Admin overview, live stats, department breakdown
|   |-- tickets.php         # All tickets with search and filter bar
|   |-- ticket_detail.php   # Full ticket management, chat, history log
|   |-- users.php           # Add, toggle, and delete users
|   |-- departments.php     # Add and toggle departments
|   +-- reports.php         # Analytics by status, priority, dept, category
|-- student/
|   |-- dashboard.php       # Student home, personal stats, recent tickets
|   |-- new_ticket.php      # Submit new ticket form with validation
|   |-- my_tickets.php      # List all personal tickets
|   |-- ticket_view.php     # View ticket details and send follow-up messages
|   +-- ticket_history.php  # Read-only audit log view
|-- includes/
|   |-- db.php              # MySQL connection via mysqli
|   |-- auth.php            # Session management, access guards, helper functions
|   |-- sidebar_admin.php   # Admin navigation sidebar
|   +-- sidebar_student.php # Student navigation sidebar
|-- assets/
|   +-- style.css           # Complete custom stylesheet
|-- index.php               # Login page and system entry point
|-- logout.php              # Session destroy and redirect
+-- csit226finals.sql       # Full database schema and seed data
```

---

## Installation

### Requirements
- XAMPP with Apache, MySQL, and PHP 8.x
- Any modern web browser

### Steps

**1. Clone or download the repository**
```
git clone https://github.com/yourusername/csit226finals.git
```

**2. Move the folder to XAMPP htdocs**
```
C:\xampp\htdocs\csit226finals\
```

**3. Start XAMPP**
- Open XAMPP Control Panel
- Start Apache
- Start MySQL

**4. Import the database**
- Open http://localhost/phpmyadmin
- Click the SQL tab
- Paste and run the entire contents of csit226finals.sql
- Alternatively, use the Import tab, select the .sql file, and click Go

**5. Open the project in a browser**
```
http://localhost/csit226finals/
```

---

## Demo Accounts

| Role | Email | Password | Access Level |
|---|---|---|---|
| Admin | admin@university.edu | password | Full system access |
| Staff | staff@university.edu | password | Ticket management |
| Student | student@university.edu | password | Student portal |
| Faculty | faculty@university.edu | password | Student portal |
| Employee | employee@university.edu | password | Student portal |

All passwords are hashed using PHP password_hash() with PASSWORD_DEFAULT (bcrypt).

---

## Database Design

### Tables

| Table | Description | Key Fields |
|---|---|---|
| departments | University departments | DeptID (PK), DeptName, DeptEmail, IsActive |
| users | All system users with Requester and Staff merged into one table | UserID (PK), FullName, Email, Password, UserType, IsActive |
| tickets | Support tickets | TicketID (PK), TicketNo (UNIQUE), Title, Category, Description, Status, Priority, UrgentReason, Location, ResolutionSummary, DateClosed, OccurrenceDate, CreatedDate |
| followups | Chat messages per ticket | FollowupID (PK), TicketID (FK), SenderID (FK), Message, SentAt |
| history_log | Audit trail of all ticket changes | LogID (PK), TicketID (FK), UpdatedBy (FK), UpdateNote, OldStatus, NewStatus, ReassignReason, UpdateDate |

### Relationships
- Department to Ticket: one department has many tickets (mandatory)
- User as Requester to Ticket: one user files many tickets (mandatory)
- User as Staff to Ticket: one staff member is optionally assigned to many tickets
- Ticket to Followups: one ticket has many messages, cascade delete applied
- Ticket to History Log: one ticket has many log entries, cascade delete applied

### Seeded Data
- 8 departments
- 5 users, one per role
- 5 sample tickets with various statuses and priorities
- 7 follow-up messages
- 13 history log entries

---

## CRUD Implementation

### Create
| Feature | File | Table |
|---|---|---|
| Submit new ticket | student/new_ticket.php | tickets |
| Log ticket creation | student/new_ticket.php | history_log |
| Send follow-up message as student | student/ticket_view.php | followups |
| Send reply as admin | admin/ticket_detail.php | followups |
| Log every status update | admin/ticket_detail.php | history_log |
| Add new user | admin/users.php | users |
| Add new department | admin/departments.php | departments |

### Read
| Feature | File | Table |
|---|---|---|
| Student views own tickets | student/my_tickets.php | tickets |
| Student views ticket detail and chat | student/ticket_view.php | tickets, followups |
| Student views history log | student/ticket_history.php | history_log |
| Student dashboard statistics | student/dashboard.php | tickets |
| Admin views all tickets with filters | admin/tickets.php | tickets, departments, users |
| Admin views ticket detail, chat, and log | admin/ticket_detail.php | tickets, followups, history_log |
| Admin dashboard live statistics | admin/dashboard.php | tickets, departments |
| Admin views all users | admin/users.php | users, departments |
| Admin views all departments | admin/departments.php | departments |
| Admin views reports | admin/reports.php | tickets, departments |
| Login credential verification | index.php | users |

### Update
| Feature | File | Table |
|---|---|---|
| Change ticket status | admin/ticket_detail.php | tickets |
| Assign or reassign staff | admin/ticket_detail.php | tickets |
| Add resolution summary | admin/ticket_detail.php | tickets |
| Set DateClosed on closure | admin/ticket_detail.php | tickets |
| Toggle user active or inactive | admin/users.php | users |
| Toggle department active or inactive | admin/departments.php | departments |

### Delete
| Feature | File | Table |
|---|---|---|
| Delete a user permanently | admin/users.php | users |
| Delete a department | admin/departments.php | departments |
| Delete ticket cascades to messages and logs | phpMyAdmin or SQL | tickets, followups, history_log |

### Design Decisions
- Students have no Update or Delete access by design, as per Business Rule 6, to ensure accountability and data integrity.
- Users and Departments use soft delete via the IsActive flag rather than hard delete to preserve historical ticket data.
- The followups and history_log tables use ON DELETE CASCADE so that deleting a ticket automatically removes all related records.

---

## Business Rules

| No. | Rule | Status | Enforced In |
|---|---|---|---|
| 1 | All users must log in via email and password; dashboard provides Submit, My Tickets, and History sections | Implemented | index.php, auth.php, sidebars |
| 2 | Every ticket must have a unique TicketNo and a non-null CreatedDate | Implemented | auth.php generateTicketNo(), DB UNIQUE constraint |
| 3 | A ticket must be linked to exactly one Requester and one Department | Implemented | new_ticket.php validation, DB Foreign Keys |
| 4 | Category is required; Description must be at least 50 characters | Implemented | new_ticket.php PHP validation and JS counter |
| 5 | Status must come from a fixed list: Open, Assigned, In Progress, On Hold, Resolved, Closed | Implemented | DB ENUM, dropdown in ticket_detail.php |
| 6 | Only admins can change status to Resolved or Closed; students can only create tickets and send follow-ups | Implemented | requireAdmin(), isAdmin() in auth.php |
| 7 | A ticket can only be assigned to one active staff member at a time; reassignment requires a ReassignReason | Implemented | Single AssignedTo field, history_log table |
| 8 | Priority is required; Urgent tickets must include a written UrgentReason | Implemented | HTML required attribute, JS toggleUrgent(), PHP validation |
| 9 | Every update must save UpdatedBy, UpdateDate, and UpdateNote | Implemented | history_log table, inserted on every ticket update |
| 10 | The system must display the TicketNo to the requester immediately after submission | Implemented | Success message in new_ticket.php |
| 11 | Closing a ticket requires a ResolutionSummary and DateClosed; closed tickets are read-only | Implemented | PHP sets DateClosed, update form hidden when Closed |
| 12 | If a requester submits a ticket with the same Category within 24 hours, the system must warn of a possible duplicate | Implemented | Duplicate query in new_ticket.php |
| 13 | Dashboard must show a summary of Open tickets by department and assigned staff | Implemented | admin/dashboard.php, admin/reports.php |

---

## Code Explanation

### includes/db.php
Creates a mysqli connection to the csit226finals database on localhost. Every other PHP file loads this via require_once to access the shared $conn variable. If the connection fails, PHP throws a fatal error and stops execution.

### includes/auth.php
The security backbone of the application. Starts the PHP session on every page load and provides the following functions: requireLogin() redirects unauthenticated users to the login page; requireAdmin() and requireStaffOrAdmin() restrict page access by role; isAdmin() returns a boolean used for conditional rendering throughout the admin panel; generateTicketNo() creates unique ticket numbers in the format TKT-AA1001-25; statusBadge() and priorityBadge() return CSS class names used in every table and ticket view for color-coded labels.

### index.php
The entry point of the system. Presents a two-column login form. On form submission, it queries the users table by email and calls password_verify() against the stored bcrypt hash. On success, it saves user_id, user_type, and full_name to the PHP session and redirects based on role. Admin and Staff go to admin/dashboard.php while Student, Faculty, and Employee go to student/dashboard.php.

### logout.php
Calls session_destroy() and redirects to index.php, fully clearing the user session.

### student/new_ticket.php
The most complex student-side file. Handles a multi-field submission form with server-side PHP validation for required fields and urgent reason enforcement. Before inserting, it runs a duplicate check query looking for the same Category from the same user within the last 24 hours and displays a warning banner if found. On successful validation, it calls generateTicketNo(), inserts the record into the tickets table, logs the creation in history_log, and displays a success message with the generated TicketNo. JavaScript handles the dynamic urgent reason field and a live character counter for the description field.

### student/ticket_view.php
Fetches all follow-up messages for a ticket joined with user data. Displays messages as a chat interface where admin and staff messages appear on the right and student messages appear on the left. A reply form at the bottom inserts into the followups table and redirects back to the same page to display the new message.

### admin/ticket_detail.php
The most complex file in the project. The left column shows ticket metadata, the full chat thread, and a chronological history timeline. The right column, visible only to admins, contains the update form with a status dropdown, an assign-to dropdown populated from active staff accounts, a required update note field, an optional reassign reason, and a resolution summary field. On submission, the file updates the tickets table and inserts a new record into history_log with the old and new status values. When the ticket status is Closed, the right column is replaced with a read-only notice.

### admin/tickets.php
Builds a dynamic SQL WHERE clause based on GET parameters for status, priority, department, and keyword search. Results are ordered by priority with Urgent first, then by creation date descending. Each row links to ticket_detail.php for full management.

### admin/dashboard.php
Uses multiple COUNT() queries to populate stat cards for each ticket status. The department breakdown section uses a GROUP BY query with SUM(CASE WHEN ...) to calculate open versus total tickets per department, displayed as inline CSS progress bars.

### admin/reports.php
Uses COUNT() and GROUP BY queries to break down ticket data by Status, Priority, Department, and Category. Each section calculates percentage values and renders proportional bars using inline CSS widths. No external charting libraries are used.

### admin/users.php
Lists all users in the system. The Add User modal form hashes the submitted password using password_hash() before inserting into the database. The toggle action uses UPDATE SET IsActive = NOT IsActive for a soft delete approach. A check prevents admins from deleting their own account.

### assets/style.css
A single stylesheet controlling the entire user interface. CSS custom properties at the top define the color theme, spacing scale, and typography. The maroon primary color is defined as --color-primary: #7B1D1D. The layout uses Flexbox for the sidebar and main content areas. All components including cards, tables, badges, buttons, forms, alerts, chat bubbles, and the history timeline are built without any external CSS framework.

### Full Request Lifecycle
```
1.  Student logs in - index.php validates credentials and creates the session
2.  Student submits a ticket - new_ticket.php validates the form input
3.  Record is inserted into tickets; first entry is logged in history_log
4.  Student sees the generated TicketNo in the success message
5.  Admin logs in and sees the ticket on the dashboard and tickets list
6.  Admin opens ticket_detail.php, assigns a staff member, and changes the status
7.  The tickets table is updated and a new history_log entry is created
8.  Admin sends a reply which is inserted into followups
9.  Student refreshes ticket_view.php and sees the reply and updated status
10. Admin marks the ticket as Resolved with a ResolutionSummary and DateClosed
11. Ticket becomes fully read-only and no further changes are permitted
```

---

## License
This project was created for academic purposes as part of CSIT226 - Information Management Final Project.
