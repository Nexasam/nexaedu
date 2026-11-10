# School Management MVP Roadmap

This document captures the core features we identified after the current admin, academic setup, assessment, score entry, and permissions work.

## 1. Parent Portal

Build a dedicated parent/student-facing portal where parents can see only their linked children.

Core pages:
- Parent dashboard
- Child profile summary
- Subjects and teachers
- Attendance summary
- Fees summary
- Results/report cards

## 2. Parent Invitation Flow

Parent information should be captured while adding/importing students.

Flow:
- Add guardian details during student setup.
- Create or update a parent profile.
- Send invite link to parent.
- Parent creates account.
- Account links to existing `parent_profiles`.
- Support invite expiry, resend, and accepted status.

## 3. Parent Role And Permissions

Add `parent` as a properly scoped access type.

Rules:
- Parent can only access allowed parent portal pages.
- Parent can only access students linked through `parent_student`.
- Permissions control page access.
- Data scoping must still enforce child ownership even if permissions are misconfigured.

## 4. Report Card Publishing

Score entry completion is not the same as parent release.

Flow:
- Teacher/admin completes subject scores.
- School reviews class/student report readiness.
- School publishes report cards.
- Parents only see published reports.
- Sending to parent should depend on published/ready status.

## 5. Attendance

Build attendance tracking for daily school operations.

Core features:
- Attendance setup per class arm/session/term/date.
- Teacher/admin can mark attendance.
- Parent can view child attendance.
- Attendance summary for dashboard and report card.

## 6. Fees

Build fee setup and payment tracking.

Core features:
- Fee items per class/session/term.
- Student billing.
- Payment recording.
- Outstanding balance.
- Parent fee view.
- Basic fee reports.

## 7. Student Profile Detail Page

Create a full student record page.

Sections:
- Biodata
- Class/enrollment history
- Parent/guardian links
- Subjects and teachers
- Attendance
- Scores/results
- Fees
- Notes/documents later

## 8. Teacher Portal

Build a dedicated teacher experience.

Rules:
- Teacher sees assigned class arms and subjects only.
- Teacher can enter scores only for assigned subjects.
- Teacher can mark attendance only for assigned class arms if permitted.
- Teacher dashboard should show pending score sheets, attendance tasks, and assigned students.

## 9. Notifications

Start with in-app notifications, then later add email/SMS/WhatsApp.

Notification types:
- Parent invite
- Report card released
- Fee reminder
- Attendance alert
- Staff invite
- Score sheet completed

## 10. Audit Logs

Track sensitive actions.

Events to log:
- Score changes
- Assessment flow changes
- Score sheet completion/unlocking
- Permission changes
- School settings changes
- Parent invite actions
- Fee payment updates

## 11. Timetable

Build timetable setup and views.

Core features:
- Period setup
- Class arm timetable
- Teacher timetable
- Subject scheduling
- Conflict checks later

## 12. Admissions / Applicant Flow

Build a flow for prospective students.

Core features:
- Application form
- Applicant records
- Review/approval
- Convert applicant to student
- Parent account linkage

## 13. Promotion To Next Class / Session

Support academic year transitions.

Core features:
- Promote students to next class arm.
- Repeat/withdraw/graduate statuses.
- New session and term rollover.
- Preserve historical enrollments and results.

## 14. Behavioral Notes / Discipline

Track student conduct.

Core features:
- Behavior notes
- Discipline incidents
- Teacher/admin comments
- Parent-visible or internal-only flag

## 15. Medical Records

Track basic student health information.

Core features:
- Allergies
- Medical conditions
- Emergency contacts
- Sick bay visits
- Parent visibility controls

## 16. Document Uploads

Support student, staff, and school documents.

Examples:
- Birth certificate
- Previous school records
- Staff certificates
- School policies
- Result PDFs

## 17. Messaging Between School And Parents

Build communication tools.

Core features:
- Announcements
- Direct parent messages
- Class arm broadcasts
- Read receipts later

## 18. Result Approval Workflow

Add a formal result approval pipeline.

Flow:
- Teacher enters scores.
- Teacher marks subject as completed.
- Class teacher/admin reviews.
- School super admin approves.
- Results/report cards are published to parents.

