<?php

namespace App\Http\Controllers\Api;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'Staff Reporting Management API',
    version: '1.0.0',
    description: 'API for Staff Reporting Management System. Provides endpoints for authentication, reports, announcements, proposals, and user management.',
    contact: new OA\Contact(
        email: 'admin@example.com',
        name: 'API Support'
    )
)]
#[OA\Server(
    url: L5_SWAGGER_CONST_HOST,
    description: 'API Server'
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'token',
    description: 'Enter your Sanctum token'
)]
#[OA\Tag(name: 'Health', description: 'API health check endpoints')]
#[OA\Tag(name: 'Authentication', description: 'API endpoints for user authentication')]
#[OA\Tag(name: 'Users', description: 'API endpoints for user management')]
#[OA\Tag(name: 'Reports', description: 'API endpoints for report management')]
#[OA\Tag(name: 'Departments', description: 'API endpoints for department management')]
#[OA\Tag(name: 'Announcements', description: 'API endpoints for announcements')]
#[OA\Tag(name: 'Proposals', description: 'API endpoints for proposals')]
#[OA\Tag(name: 'Comments', description: 'API endpoints for comments on reports and proposals')]
#[OA\Tag(name: 'Notifications', description: 'API endpoints for user notifications')]
#[OA\Tag(name: 'Settings', description: 'API endpoints for site settings management')]
#[OA\Schema(
    schema: 'SuccessResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string', example: 'Success'),
        new OA\Property(property: 'data', type: 'object'),
    ]
)]
#[OA\Schema(
    schema: 'ErrorResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'message', type: 'string', example: 'Error message'),
        new OA\Property(property: 'errors', type: 'object'),
    ]
)]
#[OA\Schema(
    schema: 'ValidationErrorResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'message', type: 'string', example: 'Validation failed'),
        new OA\Property(property: 'errors', type: 'object'),
    ]
)]
#[OA\Schema(
    schema: 'PaginationMeta',
    type: 'object',
    properties: [
        new OA\Property(property: 'current_page', type: 'integer', example: 1),
        new OA\Property(property: 'last_page', type: 'integer', example: 10),
        new OA\Property(property: 'per_page', type: 'integer', example: 15),
        new OA\Property(property: 'total', type: 'integer', example: 150),
        new OA\Property(property: 'from', type: 'integer', example: 1),
        new OA\Property(property: 'to', type: 'integer', example: 15),
    ]
)]
#[OA\Schema(
    schema: 'PaginationLinks',
    type: 'object',
    properties: [
        new OA\Property(property: 'first', type: 'string', example: 'http://example.com/api/v1/resource?page=1'),
        new OA\Property(property: 'last', type: 'string', example: 'http://example.com/api/v1/resource?page=10'),
        new OA\Property(property: 'prev', type: 'string', nullable: true),
        new OA\Property(property: 'next', type: 'string', example: 'http://example.com/api/v1/resource?page=2'),
    ]
)]
#[OA\Schema(
    schema: 'Department',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Engineering'),
        new OA\Property(property: 'description', type: 'string', example: 'Software development team'),
        new OA\Property(property: 'staff_count', type: 'integer', example: 15),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
    ]
)]
#[OA\Schema(
    schema: 'User',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'kingschat_id', type: 'string', example: 'john.doe'),
        new OA\Property(property: 'first_name', type: 'string', example: 'John'),
        new OA\Property(property: 'last_name', type: 'string', example: 'Doe'),
        new OA\Property(property: 'full_name', type: 'string', example: 'John Doe'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
        new OA\Property(property: 'masked_phone', type: 'string', example: '****5678'),
        new OA\Property(property: 'department', ref: '#/components/schemas/Department', nullable: true),
        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'), example: ['staff']),
        new OA\Property(property: 'permissions', type: 'array', items: new OA\Items(type: 'string'), example: ['view_reports']),
        new OA\Property(property: 'profile_photo_url', type: 'string', nullable: true),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'last_login_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'AuthUser',
    type: 'object',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/User'),
        new OA\Schema(
            properties: [
                new OA\Property(property: 'unread_notifications_count', type: 'integer', example: 5),
                new OA\Property(property: 'token', type: 'string', example: '1|abc123...', description: 'Only present on login response'),
            ]
        ),
    ]
)]
#[OA\Schema(
    schema: 'Report',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Weekly Status Report'),
        new OA\Property(property: 'description', type: 'string', example: 'Summary of weekly activities'),
        new OA\Property(property: 'report_type', type: 'string', enum: ['personal', 'department'], example: 'personal'),
        new OA\Property(property: 'report_category', type: 'string', enum: ['daily', 'weekly', 'monthly', 'quarterly', 'annual'], example: 'weekly'),
        new OA\Property(property: 'status', type: 'string', enum: ['draft', 'submitted', 'reviewed', 'approved', 'rejected'], example: 'draft'),
        new OA\Property(property: 'status_badge_class', type: 'string', example: 'badge-secondary'),
        new OA\Property(property: 'file_name', type: 'string', nullable: true, example: 'report.pdf'),
        new OA\Property(property: 'file_type', type: 'string', nullable: true, example: 'pdf'),
        new OA\Property(property: 'file_size', type: 'integer', nullable: true, example: 1048576),
        new OA\Property(property: 'formatted_file_size', type: 'string', example: '1.00 MB'),
        new OA\Property(property: 'file_icon', type: 'string', example: 'file-pdf'),
        new OA\Property(property: 'file_color', type: 'string', example: 'red'),
        new OA\Property(property: 'file_url', type: 'string', nullable: true),
        new OA\Property(property: 'is_viewable_in_browser', type: 'boolean', example: true),
        new OA\Property(property: 'can_be_downloaded', type: 'boolean', example: true),
        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
        new OA\Property(property: 'department', ref: '#/components/schemas/Department', nullable: true),
        new OA\Property(property: 'reviewer', ref: '#/components/schemas/User', nullable: true),
        new OA\Property(property: 'comments', type: 'array', items: new OA\Items(ref: '#/components/schemas/Comment')),
        new OA\Property(property: 'comments_count', type: 'integer', example: 3),
        new OA\Property(property: 'review_notes', type: 'string', nullable: true),
        new OA\Property(property: 'reviewed_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'submitted_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Comment',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'content', type: 'string', example: 'Great work on this report!'),
        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
        new OA\Property(property: 'parent_id', type: 'integer', nullable: true),
        new OA\Property(property: 'replies', type: 'array', items: new OA\Items(ref: '#/components/schemas/Comment')),
        new OA\Property(property: 'replies_count', type: 'integer', example: 2),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Announcement',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'System Maintenance'),
        new OA\Property(property: 'content', type: 'string', example: 'The system will be down for maintenance...'),
        new OA\Property(property: 'priority', type: 'string', enum: ['low', 'medium', 'high', 'urgent'], example: 'high'),
        new OA\Property(property: 'priority_badge_class', type: 'string', example: 'badge-warning'),
        new OA\Property(property: 'target_type', type: 'string', enum: ['all', 'departments', 'users', 'roles'], example: 'all'),
        new OA\Property(property: 'is_pinned', type: 'boolean', example: false),
        new OA\Property(property: 'starts_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'expires_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'is_expired', type: 'boolean', example: false),
        new OA\Property(property: 'is_scheduled', type: 'boolean', example: false),
        new OA\Property(property: 'creator', ref: '#/components/schemas/User'),
        new OA\Property(property: 'departments', type: 'array', items: new OA\Items(ref: '#/components/schemas/Department')),
        new OA\Property(property: 'targeted_user_ids', type: 'array', items: new OA\Items(type: 'integer')),
        new OA\Property(property: 'read_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'is_read', type: 'boolean', example: false),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Proposal',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'New Project Proposal'),
        new OA\Property(property: 'description', type: 'string', example: 'Description of the proposed project'),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'under_review', 'approved', 'rejected'], example: 'pending'),
        new OA\Property(property: 'status_badge_class', type: 'string', example: 'badge-warning'),
        new OA\Property(property: 'file_name', type: 'string', nullable: true, example: 'proposal.pdf'),
        new OA\Property(property: 'file_type', type: 'string', nullable: true, example: 'pdf'),
        new OA\Property(property: 'file_size', type: 'integer', nullable: true, example: 1048576),
        new OA\Property(property: 'formatted_file_size', type: 'string', example: '1.00 MB'),
        new OA\Property(property: 'file_url', type: 'string', nullable: true),
        new OA\Property(property: 'has_attachment', type: 'boolean', example: true),
        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
        new OA\Property(property: 'reviewer', ref: '#/components/schemas/User', nullable: true),
        new OA\Property(property: 'comments', type: 'array', items: new OA\Items(ref: '#/components/schemas/Comment')),
        new OA\Property(property: 'comments_count', type: 'integer', example: 3),
        new OA\Property(property: 'admin_notes', type: 'string', nullable: true),
        new OA\Property(property: 'reviewed_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Notification',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid', example: '9c7f8e6a-5b4d-3c2a-1e0f-9d8c7b6a5f4e'),
        new OA\Property(property: 'type', type: 'string', enum: ['comment', 'announcement', 'report_status', 'proposal_status', 'system'], example: 'comment'),
        new OA\Property(property: 'title', type: 'string', example: 'New Comment'),
        new OA\Property(property: 'message', type: 'string', example: 'John Doe commented on your report: "Weekly Status Report"'),
        new OA\Property(property: 'data', type: 'object', example: '{"comment_id": 1, "report_id": 5}'),
        new OA\Property(property: 'icon', type: 'string', example: 'chat-bubble-left-ellipsis'),
        new OA\Property(property: 'color', type: 'string', example: 'blue'),
        new OA\Property(property: 'is_read', type: 'boolean', example: false),
        new OA\Property(property: 'read_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'Setting',
    type: 'object',
    properties: [
        new OA\Property(property: 'key', type: 'string', example: 'site_name'),
        new OA\Property(property: 'value', type: 'string', example: 'Staff Reporting Management', nullable: true),
        new OA\Property(property: 'type', type: 'string', enum: ['text', 'textarea', 'image', 'boolean', 'json', 'color', 'number'], example: 'text'),
        new OA\Property(property: 'group', type: 'string', enum: ['general', 'appearance', 'email', 'reports', 'features'], example: 'general'),
        new OA\Property(property: 'label', type: 'string', example: 'Site Name', nullable: true),
        new OA\Property(property: 'description', type: 'string', nullable: true),
    ]
)]
class OpenApiSpec
{
    // This class exists only to hold OpenAPI annotations
}
