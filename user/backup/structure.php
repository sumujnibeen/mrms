mrms/
│
│ ═══════════════════════════════════════
│ DATABASE: mrms_db
│ ═══════════════════════════════════════
│ 📋 announcement → Announcement_id, Title, Message, PDF_File,
│ Type, Link, Start_date, End_date
│
│ 📋 booking → booking_id, user_id, service_type, vehicle_id,
│ hotel_id, guide_id, event_id, booking_date,
│ booking_time, destination, contact_number, booked_at
│
│ 📋 destination → Destination_id, Name, Description, Location,
│ Photo, Rating, Category
│
│ 📋 event → Event_id, Name, Description, Location, Start_date,
│ End_date, Photo, Destination_id, Ticket_price,
│ Seats_available, Rating, Created_at, availability
│
│ 📋 guide → Guide_id, Name, Phone, Email, Language, Price,
│ Photo, Rating↑, availability
│
│ 📋 hotel → Hotel_id, Name, Description, Location, Photo,
│ Rating↑, Price, Rooms_available, Destination_id
│
│ 📋 review → Review_id, User_id, Target_type, Target_id,
│ Rating, Comment, Created_at
│
│ 📋 user → User_id, Name, Email, Password, Role,
│ Photo, Created_at
│
│ 📋 vehicle → vehicle_id, vehicle_name, vehicle_type,
│ vehicle_price, vehicle_availability_number↑,
│ vehicle_image, vehicle_description,
│ vehicle_location, vehicle_route, created_at
│ ═══════════════════════════════════════
│
├── db.php ← Database connection
├── auth.php ← Session check (login required)
├── global_css.php ← Common CSS/Bootstrap link
│
├── index.php ← Homepage (Hero + Quick links)
├── navbar.php ← Dynamic navbar (role-based)
├── footer.php ← Footer
│
├── login.php ← Login page
├── register.php ← Guest registration
├── logout.php ← Session destroy
│
├── rooms.php ← All rooms listing
├── room_details.php ← Single room details + Book button
├── booking.php ← Booking form (date picker)
├── booking_manager.php ← Booking logic (PHP backend)
├── my_bookings.php ← Guest: my booking history
│
├── sslcommerz/
│ ├── init.php
│ ├── success.php
│ ├── fail.php
│ └── cancel.php
├── payment_success.php
└── payment_fail.php
│
├── my_stay.php
├── food_menu.php ← Browse food menu
├── service_request.php ← Place food/room service request
├── service_manager.php ← Service request backend

├── announcement.php ← Public announcements
│
├── admin/
│ ├── dashboard.php ← Admin dashboard (stats + chart)
│ ├── manage_rooms.php ← Add/Edit/Delete rooms
│ ├── manage_bookings.php ← All bookings list
│ ├── manage_users.php ← All users
│ ├── manage_food.php ← Food menu management
│ ├── manage_services.php ← Service requests
│ ├── manage_announcements.php ← Add/Edit announcements
│ ├── invoice_view.php ← View/Print invoice
│ └── reports.php ← Revenue + occupancy report
│
├── receptionist/
│ ├── checkin.php ← Process check-in
│ ├── checkout.php ← Process check-out + invoice
│ ├── manage_bookings.php ← Receptionist booking view
│ └── generate_invoice.php ← Invoice generator
│
└── assets/
├── images/
│ ├── logo_blue.png
│ ├── logo_white.png
│ ├── favicon.png
│ ├── rooms/
│ ├── food/
│ └── users/
└── css/
└── custom.css ← Bootstrap Blue override




new stureture
mrms/
├── admin/
│ ├── dashboard.php (teammate করবে)
│ ├── finance.php
│ ├── manage_announcements.php
│ ├── manage_bookings.php
│ ├── manage_food.php
│ ├── manage_rooms.php
│ ├── manage_services.php
│ ├── manage_users.php
│ └── reports.php
│
├── receptionist/
│ ├── checkin_content.php
│ ├── checkout_content.php
│ ├── generate_invoice.php
│ ├── manage_bookings_content.php
│ └── reception.php
│
├── sslcommerz/
│ ├── cancel.php
│ ├── fail.php
│ ├── init.php
│ └── success.php
│
├── user/ ← নতুন folder
│ ├── cancel_booking.php
│ ├── current_bill.php
│ ├── food_order_content.php
│ ├── my_bookings.php
│ ├── my_bookings_content.php
│ ├── my_stay.php
│ ├── past_requests_content.php
│ ├── payment.php
│ ├── payment_fail.php
│ ├── payment_success.php
│ ├── refund_request.php
│ ├── review_submit.php
│ └── service_content.php
│
├── assets/
├── announcement.php
├── auth.php
├── booking_manager.php
├── db.php
├── food_menu.php
├── footer.php
├── global_css.php
├── index.php
├── login.php
├── logout.php
├── navbar.php
├── register.php
├── review_widget.php ← root-এ থাকে (receptionist ও use করে)
├── room_details.php
├── rooms.php
└── service_submit.php ← root-এ থাকে (receptionist ও use করে)