# Staff Reporting Management System - Step-by-Step Implementation Prompts

## System Overview
A Laravel-based staff reporting management system with:
- KingsChat ID + Phone verification login (no registration)
- Role-based access (Super Admin, Admin, Head of Operations, HOD, Staff)
- Multi-format report uploads (PDF, Word, Excel, PowerPoint, Video, Image)
- Comments with email/KingsChat notifications
- Announcements system
- Proposals module
- CMS for admin customization

---

## PHASE 1: Project Setup & Configuration

### Prompt 1.1 - Initialize Laravel Project
```
Create a new Laravel project with the following setup:
1. Install Laravel 11.x
2. Configure Tailwind CSS with Laravel
3. Install Alpine.js via npm
4. Install required packages:
   - spatie/laravel-permission (roles & permissions)
   - spatie/laravel-medialibrary (file uploads)
   - maatwebsite/excel (Excel import/export)
   - barryvdh/laravel-dompdf (PDF generation)
   - laravel/scout with meilisearch (search functionality)

Run these commands:
composer create-project laravel/laravel staff-reporting-management
cd staff-reporting-management
composer require spatie/laravel-permission spatie/laravel-medialibrary maatwebsite/excel barryvdh/laravel-dompdf
npm install -D tailwindcss postcss autoprefixer alpinejs
npx tailwindcss init -p
```

### Prompt 1.2 - Configure Environment
```
Update the .env file with:
- Database connection (MySQL)
- Mail configuration (for notifications)
- App name and URL
- File storage configuration

Create a config file for KingsChat integration settings.
```

### Prompt 1.3 - Tailwind & Alpine Setup
```
Configure tailwind.config.js to scan Blade templates.
Set up Alpine.js in resources/js/app.js.
Create the main app.blade.php layout with:
- Responsive sidebar navigation
- Top navbar with user dropdown
- Main content area
- Toast notification component
- Modal component using Alpine.js
```

---

## PHASE 2: Database Design & Migrations

### Prompt 2.1 - Create User Migration
```
Create a users table migration with fields:
- id (primary key)
- kingschat_id (unique, required)
- first_name
- last_name
- email (nullable)
- phone (for verification)
- department_id (foreign key)
- profile_photo
- is_active (boolean)
- last_login_at
- timestamps

The kingschat_id and phone combination will be used for authentication.
```

### Prompt 2.2 - Create Departments Migration
```
Create a departments table with:
- id
- name
- description
- head_id (foreign key to users - the HOD)
- parent_id (for sub-departments, nullable)
- is_active
- timestamps
```

### Prompt 2.3 - Create Reports Migration
```
Create a reports table with:
- id
- user_id (who submitted)
- department_id
- title
- description
- report_type (enum: personal, department)
- report_category (enum: daily, weekly, monthly, quarterly, annual)
- file_path
- file_type (pdf, docx, xlsx, pptx, video, image)
- file_size
- status (draft, submitted, reviewed, approved, rejected)
- submitted_at
- reviewed_by
- reviewed_at
- timestamps
- soft deletes
```

### Prompt 2.4 - Create Comments Migration
```
Create a comments table with:
- id
- commentable_type (polymorphic - for reports, proposals)
- commentable_id
- user_id
- content
- parent_id (for nested comments)
- timestamps
- soft deletes
```

### Prompt 2.5 - Create Announcements Migration
```
Create announcements table with:
- id
- title
- content (rich text)
- created_by (user_id)
- priority (low, medium, high, urgent)
- is_pinned
- starts_at
- expires_at
- timestamps

Create announcement_user pivot table for targeted announcements:
- announcement_id
- user_id
- read_at (nullable)
```

### Prompt 2.6 - Create Proposals Migration
```
Create proposals table with:
- id
- user_id
- title
- description
- file_path (optional attachment)
- status (pending, under_review, approved, rejected)
- admin_notes
- reviewed_by
- reviewed_at
- timestamps
- soft deletes
```

### Prompt 2.7 - Create Notifications Migration
```
Create a custom notifications table with:
- id
- user_id
- type (comment, announcement, report_status, proposal_status)
- title
- message
- data (JSON for additional info)
- read_at
- timestamps
```

### Prompt 2.8 - Create CMS Settings Migration
```
Create site_settings table with:
- id
- key (unique)
- value (text/JSON)
- type (text, textarea, image, boolean, json)
- group (general, appearance, email, etc.)
- timestamps

Create pages table for CMS pages:
- id
- title
- slug
- content (rich text)
- meta_title
- meta_description
- is_published
- timestamps
```

---

## PHASE 3: Models & Relationships

### Prompt 3.1 - Create User Model
```
Create the User model with:
- Relationships: department, reports, comments, proposals, notifications
- HasRoles trait from Spatie
- InteractsWithMedia trait
- Scopes: active(), byDepartment()
- Methods: isHOD(), isAdmin(), canViewReport(), getFullNameAttribute()
```

### Prompt 3.2 - Create Department Model
```
Create Department model with:
- Relationships: users, head (user), reports, parent, children
- Scopes: active(), root()
- Methods: getStaffCount(), getAllStaffIds()
```

### Prompt 3.3 - Create Report Model
```
Create Report model with:
- Relationships: user, department, comments, reviewer
- InteractsWithMedia trait for file handling
- Scopes: byCategory(), byType(), byStatus(), byDepartment()
- Methods: getFileIcon(), isViewableInBrowser(), canBeDownloaded()
```

### Prompt 3.4 - Create Remaining Models
```
Create models for:
- Comment (polymorphic, nested)
- Announcement (with user targeting)
- Proposal
- Notification
- SiteSetting
- Page
```

---

## PHASE 4: Authentication System

### Prompt 4.1 - Create Login Flow
```
Build custom authentication without registration:

1. Login page asks for KingsChat ID
2. System checks if KingsChat ID exists in database
3. If exists, show phone verification step (partial phone display: ****1234)
4. User enters full phone number
5. If phone matches, generate OTP (optional) or log in directly
6. Create session and redirect based on role

Create:
- LoginController with methods: showLoginForm, verifyKingsChatId, verifyPhone
- Login Blade views with Alpine.js for step transitions
- Custom guard if needed
```

### Prompt 4.2 - Create Middleware
```
Create middleware for:
- CheckRole: Verify user has required role for route
- CheckDepartmentAccess: HOD can only see their department
- CheckActive: Ensure user account is active
- LogActivity: Track user actions

Register middleware in bootstrap/app.php
```

---

## PHASE 5: Roles & Permissions Setup

### Prompt 5.1 - Create Roles Seeder
```
Create a seeder for roles and permissions:

Roles:
- super_admin (all permissions)
- admin (manage users, view all reports, announcements)
- head_of_operations (view all reports, manage departments)
- hod (manage department staff, view department reports)
- staff (submit reports, view own reports)

Permissions:
- users.view, users.create, users.edit, users.delete
- reports.view_all, reports.view_department, reports.view_own
- reports.create, reports.edit, reports.delete, reports.download
- comments.create, comments.delete
- announcements.view, announcements.create, announcements.edit
- proposals.view_all, proposals.view_own, proposals.create, proposals.review
- settings.manage
```

### Prompt 5.2 - User Import System
```
Create a system for Super Admin to upload users via Excel:

1. Create UserImportController
2. Create Excel import class using Maatwebsite/Excel
3. Excel template with columns: kingschat_id, first_name, last_name, email, phone, department, role
4. Validate data before import
5. Show preview before confirming import
6. Handle duplicates and errors gracefully
```

---

## PHASE 6: Dashboard Development

### Prompt 6.1 - Admin Dashboard
```
Create admin dashboard showing:
- Total users, active users count
- Reports statistics (by category, by status)
- Recent reports list with file type icons
- Recent comments activity
- Pending proposals count
- Quick actions: Upload users, Create announcement
- Charts using Chart.js or ApexCharts
```

### Prompt 6.2 - HOD Dashboard
```
Create HOD dashboard showing:
- Department staff list
- Department reports (personal + department)
- Pending reports to review
- Staff activity summary
- Department announcements
```

### Prompt 6.3 - Staff Dashboard
```
Create staff dashboard showing:
- My reports with status
- Pending report categories (what's due)
- Comments on my reports
- Announcements
- My proposals and their status
- Quick action: Submit new report
```

---

## PHASE 7: Report Management

### Prompt 7.1 - Report Upload System
```
Create comprehensive report upload:

1. ReportController with CRUD operations
2. Upload form with:
   - Title, description
   - Report type (personal/department)
   - Category (daily, weekly, monthly, quarterly, annual)
   - File upload with drag-drop (Alpine.js)
   - Support: PDF, DOCX, XLSX, PPTX, MP4, JPG, PNG
   - File size validation (configurable limit)

3. Use Spatie Media Library for file handling
4. Generate thumbnails for images
5. Store metadata in database
```

### Prompt 7.2 - Report Viewing System
```
Create report viewer that handles all file types:

1. PDF: Use PDF.js for in-browser viewing
2. Word/Excel/PowerPoint:
   - Option A: Convert to PDF on upload
   - Option B: Use Google Docs Viewer or Microsoft Office Online viewer
   - Option C: Use LibreOffice for server-side conversion
3. Images: Direct display with lightbox
4. Videos: HTML5 video player with controls

Create ReportViewerController and viewer Blade component
```

### Prompt 7.3 - Report Listing & Filtering
```
Create report listing page with:
- Filters: category, type, status, date range, department
- Search by title/description
- Sort by date, title, status
- Pagination
- Bulk actions for admin
- Export filtered list to Excel
```

---

## PHASE 8: Comments & Notifications

### Prompt 8.1 - Comments System
```
Create commenting system:

1. CommentController for CRUD
2. Polymorphic relationship (works on reports and proposals)
3. Nested/threaded comments support
4. Real-time updates with Alpine.js
5. Mention users with @ (optional)
6. Edit and delete own comments

Blade component for comment display and form
```

### Prompt 8.2 - Email Notifications
```
Set up email notifications:

1. Create Mailable classes:
   - NewCommentNotification
   - ReportStatusChanged
   - NewAnnouncement
   - ProposalStatusChanged

2. Create notification preferences in user settings
3. Queue emails for better performance
4. Use markdown email templates
```

### Prompt 8.3 - KingsChat Integration Research
```
Research KingsChat API/SDK:

1. Find official KingsChat developer documentation
2. Look for: kingschat-php package or API endpoints
3. Implement KingsChatService class with methods:
   - sendMessage(userId, message)
   - sendNotification(userId, title, body)

4. Create fallback to email if KingsChat fails
5. Add KingsChat notification toggle in user preferences

Note: Search for "KingsChat API" or "KingsChat developer" or check if there's a webhook/bot system
```

### Prompt 8.4 - In-App Notifications
```
Create in-app notification system:

1. NotificationController
2. Notification dropdown in navbar (Alpine.js)
3. Mark as read functionality
4. Notification preferences page
5. Real-time updates (optional: use Laravel Echo + Pusher)
```

---

## PHASE 9: Announcements System

### Prompt 9.1 - Create Announcements
```
Build announcements module:

1. AnnouncementController with CRUD
2. Rich text editor (TinyMCE or Quill) for content
3. Target selection:
   - All users
   - Specific departments
   - Specific users (multi-select)
   - Specific roles

4. Schedule announcements (starts_at, expires_at)
5. Priority levels with visual indicators
6. Pin important announcements
```

### Prompt 9.2 - Display Announcements
```
Create announcement display:

1. Announcements list page for all users
2. Dashboard widget showing latest/pinned
3. Modal popup for urgent announcements on login
4. Mark as read tracking
5. Filter by priority, date, read status
```

---

## PHASE 10: Proposals Module

### Prompt 10.1 - Staff Proposals
```
Create proposal submission system:

1. ProposalController
2. Proposal form:
   - Title
   - Detailed description (rich text)
   - Optional file attachment

3. Staff can view their proposals and status
4. Edit/delete only if status is pending
```

### Prompt 10.2 - Admin Proposal Review
```
Create admin proposal review:

1. List all proposals with filters
2. View proposal details
3. Review actions: approve, reject, request_changes
4. Add admin notes
5. Notify staff of status change
```

---

## PHASE 11: CMS System

### Prompt 11.1 - Site Settings
```
Create CMS settings management:

1. SettingController
2. Settings grouped by category:
   - General: Site name, logo, favicon, description
   - Appearance: Primary color, secondary color, font
   - Email: From name, from address, signature
   - Reports: Max file size, allowed types, categories
   - Features: Enable/disable modules

3. Settings form with dynamic field types
4. Upload and manage logo/favicon
5. Cache settings for performance
```

### Prompt 11.2 - Custom Pages
```
Create CMS pages:

1. PageController with CRUD
2. Rich text editor for content
3. SEO fields (meta title, description)
4. Publish/unpublish toggle
5. Custom slugs
6. Display pages in footer or sidebar
```

### Prompt 11.3 - Theme Customization
```
Create basic theme customization:

1. Color picker for primary/secondary colors
2. Logo and favicon upload
3. Custom CSS field for advanced users
4. Preview changes before saving
5. Apply colors dynamically via CSS variables
```

---

## PHASE 12: User Management

### Prompt 12.1 - User CRUD
```
Create user management for admin:

1. UserController with full CRUD
2. User listing with search, filter by department/role/status
3. User form: all fields + role assignment + department assignment
4. Activate/deactivate users
5. Reset user's phone (for re-verification)
6. View user activity log
```

### Prompt 12.2 - Department Management
```
Create department management:

1. DepartmentController with CRUD
2. Assign HOD to department
3. Hierarchical departments (parent/child)
4. Move users between departments
5. Department statistics
```

---

## PHASE 13: Security & Optimization

### Prompt 13.1 - Security Implementation
```
Implement security measures:

1. CSRF protection (Laravel default)
2. XSS prevention in user content
3. File upload validation (mime types, size, malware scan)
4. Rate limiting on login attempts
5. SQL injection prevention (Eloquent)
6. Authorization policies for all models
7. Audit log for sensitive actions
```

### Prompt 13.2 - Performance Optimization
```
Optimize performance:

1. Database indexing on frequently queried columns
2. Eager loading relationships to prevent N+1
3. Cache frequently accessed data (settings, permissions)
4. Queue heavy operations (file processing, emails)
5. Optimize images on upload
6. Asset compilation and minification
```

---

## PHASE 14: Testing & Deployment

### Prompt 14.1 - Create Tests
```
Write tests for critical functionality:

1. Feature tests for authentication flow
2. Feature tests for report CRUD
3. Unit tests for permission checks
4. Browser tests for file upload (optional)
```

### Prompt 14.2 - Deployment Preparation
```
Prepare for deployment:

1. Production environment configuration
2. Database seeder for initial data
3. Storage link for public files
4. Queue worker setup
5. Cron job for scheduled tasks
6. Backup strategy
```

---

## Database Schema Diagram

```
users
├── departments (belongs to)
├── reports (has many)
├── comments (has many)
├── proposals (has many)
├── notifications (has many)
└── announcements (many to many)

departments
├── users (has many)
├── head/user (belongs to)
├── reports (has many)
└── children (self-referential)

reports
├── user (belongs to)
├── department (belongs to)
├── comments (morphMany)
├── media (spatie media library)
└── reviewer/user (belongs to)

comments
├── user (belongs to)
├── commentable (morphTo - reports, proposals)
└── parent/children (self-referential)

announcements
├── creator/user (belongs to)
└── users (many to many with read_at)

proposals
├── user (belongs to)
├── comments (morphMany)
└── reviewer/user (belongs to)

site_settings
└── standalone key-value store

pages
└── standalone CMS pages
```

---

## File Structure Overview

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/
│   │   │   └── LoginController.php
│   │   ├── Admin/
│   │   │   ├── DashboardController.php
│   │   │   ├── UserController.php
│   │   │   ├── DepartmentController.php
│   │   │   ├── ReportController.php
│   │   │   ├── AnnouncementController.php
│   │   │   ├── ProposalController.php
│   │   │   └── SettingController.php
│   │   ├── Staff/
│   │   │   ├── DashboardController.php
│   │   │   ├── ReportController.php
│   │   │   └── ProposalController.php
│   │   ├── CommentController.php
│   │   └── NotificationController.php
│   ├── Middleware/
│   │   ├── CheckRole.php
│   │   └── CheckActive.php
│   └── Requests/
│       ├── StoreReportRequest.php
│       └── ...
├── Models/
│   ├── User.php
│   ├── Department.php
│   ├── Report.php
│   ├── Comment.php
│   ├── Announcement.php
│   ├── Proposal.php
│   ├── Notification.php
│   ├── SiteSetting.php
│   └── Page.php
├── Services/
│   ├── KingsChatService.php
│   └── NotificationService.php
├── Policies/
│   ├── ReportPolicy.php
│   └── ...
└── Imports/
    └── UsersImport.php

resources/views/
├── layouts/
│   └── app.blade.php
├── auth/
│   └── login.blade.php
├── admin/
│   ├── dashboard.blade.php
│   ├── users/
│   ├── departments/
│   ├── reports/
│   ├── announcements/
│   ├── proposals/
│   └── settings/
├── staff/
│   ├── dashboard.blade.php
│   ├── reports/
│   └── proposals/
├── components/
│   ├── report-viewer.blade.php
│   ├── comment-section.blade.php
│   ├── file-upload.blade.php
│   └── notification-dropdown.blade.php
└── partials/
    ├── sidebar.blade.php
    └── navbar.blade.php
```

---

## Quick Start Commands

```bash
# 1. Create Laravel project
composer create-project laravel/laravel staff-reporting-management
cd staff-reporting-management

# 2. Install PHP packages
composer require spatie/laravel-permission spatie/laravel-medialibrary maatwebsite/excel barryvdh/laravel-dompdf

# 3. Install Node packages
npm install -D tailwindcss postcss autoprefixer
npm install alpinejs

# 4. Initialize Tailwind
npx tailwindcss init -p

# 5. Publish package configs
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider"

# 6. Run migrations
php artisan migrate

# 7. Seed initial data
php artisan db:seed

# 8. Create storage link
php artisan storage:link

# 9. Start development
npm run dev
php artisan serve
```

---

## Notes

1. **KingsChat Integration**: You'll need to research the KingsChat API. Look for:
   - Official developer documentation
   - PHP SDK/package on Packagist
   - REST API endpoints
   - OAuth or API key authentication

2. **File Viewing**: For Office documents, consider:
   - Microsoft Office Online viewer (free, requires public URL)
   - Google Docs Viewer (free, requires public URL)
   - LibreOffice server-side conversion (self-hosted)
   - Commercial solutions like GroupDocs

3. **Real-time Features**: For live notifications, consider:
   - Laravel Echo + Pusher (easy setup)
   - Laravel Reverb (self-hosted)
   - Simple polling with Alpine.js (simpler)

4. **Scalability**: If expecting many users/files:
   - Use S3 or similar for file storage
   - Implement file chunked uploads
   - Use Redis for caching and queues
