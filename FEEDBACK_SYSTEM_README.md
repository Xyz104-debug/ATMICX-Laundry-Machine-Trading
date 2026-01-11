# Client Feedback System Documentation

## Overview
A comprehensive feedback system that allows clients to submit ratings and feedback to managers, and enables managers to view, filter, respond to, and manage client feedback.

## Features

### Client Features
- ⭐ Submit feedback with 5-star rating system
- 📝 Categorized feedback (General, Service Quality, Product Quality, Customer Support, Pricing, Delivery, Other)
- 📋 View feedback history
- 👀 See manager responses
- ✅ Track feedback status (New, Reviewed, Responded, Resolved)

### Manager Features
- 📊 Dashboard with feedback statistics
- 🔍 Filter feedback by status, category, and rating
- 💬 Respond to client feedback
- 📈 View average rating and feedback analytics
- 🔔 Badge notification for new feedback
- ✏️ Update feedback status

## Database Structure

### Table: `client_feedback`

| Column | Type | Description |
|--------|------|-------------|
| Feedback_ID | INT(11) | Primary key, auto-increment |
| Client_ID | INT(11) | Foreign key to client table |
| Rating | INT(1) | Rating from 1 to 5 stars |
| Category | VARCHAR(50) | Feedback category |
| Message | TEXT | Feedback message content |
| Status | ENUM | Status: new, reviewed, responded, resolved |
| Manager_Response | TEXT | Manager's response (nullable) |
| Responded_At | DATETIME | Timestamp when manager responded |
| Created_At | DATETIME | Timestamp when feedback was submitted |

## Installation

1. **Update Database:**
   ```sql
   -- Run this in phpMyAdmin or MySQL CLI
   source install_feedback_system.sql
   ```

2. **Files Added:**
   - `client_feedback_api.php` - API for client feedback submission
   - `feedback_api.php` - API for manager feedback management
   - `install_feedback_system.sql` - Database setup script

3. **Files Modified:**
   - `clientNEW.php` - Added feedback section to client portal
   - `atmicxMANAGER.php` - Added feedback management to manager dashboard
   - `atmicxdb.sql` - Updated with feedback table structure

## API Endpoints

### Client API (`client_feedback_api.php`)

#### Submit Feedback
**Method:** `POST`

**Request Body:**
```json
{
  "rating": 5,
  "category": "Service Quality",
  "message": "Excellent service and quick response time!"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Feedback submitted successfully",
  "feedback_id": 123
}
```

#### Get Feedback History
**Method:** `GET`

**Response:**
```json
{
  "success": true,
  "feedbacks": [
    {
      "Feedback_ID": 123,
      "Rating": 5,
      "Category": "Service Quality",
      "Message": "Excellent service!",
      "Status": "responded",
      "Manager_Response": "Thank you for your feedback!",
      "Responded_At": "2026-01-12 14:30:00",
      "Created_At": "2026-01-12 10:00:00"
    }
  ]
}
```

### Manager API (`feedback_api.php`)

#### Get All Feedback (with filters)
**Method:** `GET`

**Query Parameters:**
- `status` - Filter by status (new, reviewed, responded, resolved)
- `category` - Filter by category
- `rating` - Filter by rating (1-5)
- `date_from` - Filter by start date (YYYY-MM-DD)
- `date_to` - Filter by end date (YYYY-MM-DD)

**Response:**
```json
{
  "success": true,
  "feedbacks": [...],
  "stats": {
    "total_feedback": 100,
    "avg_rating": 4.5,
    "new_count": 5,
    "reviewed_count": 10,
    "responded_count": 75,
    "resolved_count": 10,
    "five_star": 50,
    "four_star": 30,
    "three_star": 15,
    "two_star": 3,
    "one_star": 2
  }
}
```

#### Get Single Feedback Detail
**Method:** `GET`

**Query Parameters:**
- `action=detail`
- `id={feedback_id}`

#### Update Feedback Status
**Method:** `PUT`

**Query Parameters:**
- `action=status`

**Request Body:**
```json
{
  "feedback_id": 123,
  "status": "reviewed"
}
```

#### Respond to Feedback
**Method:** `POST`

**Query Parameters:**
- `action=respond`

**Request Body:**
```json
{
  "feedback_id": 123,
  "response": "Thank you for your valuable feedback. We appreciate your input!"
}
```

## Usage Guide

### For Clients

1. **Access Feedback Section:**
   - Log in to client portal
   - Click "Feedback" in the sidebar

2. **Submit Feedback:**
   - Select rating (1-5 stars)
   - Choose feedback category
   - Write detailed message (min 10 characters)
   - Click "Submit Feedback"

3. **View Feedback History:**
   - Scroll down to see all submitted feedback
   - Check status and manager responses

### For Managers

1. **Access Feedback Management:**
   - Log in to manager dashboard
   - Click "Client Feedback" in the sidebar

2. **View Statistics:**
   - See total feedback count
   - Monitor new feedback (badge notification)
   - Check average rating

3. **Filter Feedback:**
   - Use status filter dropdown
   - Use rating filter dropdown
   - Click "Refresh" to update

4. **Respond to Feedback:**
   - Click "Respond" button on any feedback
   - Write response message
   - Click "Send Response"

5. **Update Status:**
   - Click "Mark Reviewed" for new feedback
   - Click "Mark Resolved" after responding

## Validation Rules

### Client Submission
- Rating: Required, must be 1-5
- Category: Required, must be from allowed list
- Message: Required, 10-1000 characters

### Manager Response
- Response: Required, minimum 10 characters
- Automatically sets status to "responded"
- Records timestamp of response

## Logging

Feedback system includes logging for:
- **Feedback Submissions:** `logs/system/feedback_submissions.log`
- **Manager Responses:** `logs/system/feedback_responses.log`

Log format:
```
YYYY-MM-DD HH:MM:SS - Client ID: X submitted feedback ID: Y with rating: Z
YYYY-MM-DD HH:MM:SS - Manager responded to feedback ID: Y
```

## Security Features

- ✅ Session-based authentication
- ✅ Role-based access control
- ✅ Input validation and sanitization
- ✅ SQL injection prevention (PDO prepared statements)
- ✅ XSS protection
- ✅ CSRF protection via session verification

## Styling

The feedback system uses the existing ATMICX design theme:
- Navy dark background for sidebar
- Gold accent colors for important elements
- Clean, modern card-based layout
- Responsive design for all screen sizes
- Font Awesome icons for visual clarity

## Troubleshooting

### Client cannot submit feedback
- Check client is logged in with valid session
- Verify database connection
- Check logs folder has write permissions
- Ensure all form fields are filled correctly

### Manager cannot see feedback
- Verify manager authentication and role
- Check database connection
- Ensure feedback table exists and has data
- Check browser console for JavaScript errors

### Database errors
- Run `install_feedback_system.sql` to create table
- Verify foreign key constraint (client table must exist)
- Check database user permissions

## Future Enhancements

Potential features for future development:
- Email notifications for new feedback
- Export feedback data to CSV/PDF
- Feedback analytics dashboard
- Sentiment analysis
- Automated responses
- Feedback categories customization
- Attachment support
- Multi-language support

## Support

For issues or questions about the feedback system, contact the development team or refer to the main project documentation.
