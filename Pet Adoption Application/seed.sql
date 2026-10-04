USE `petopia`;

-- Seed Users
-- admin@petopia.com -> AdminPassword123
-- user@petopia.com -> user123
INSERT INTO `users` (`id`, `name`, `email`, `phone`, `address`, `password`, `role`, `status`) VALUES
(1, 'Administrator', 'admin@petopia.com', '+1555-0000', 'Petopia Headquarters', '$2y$10$5ffSyDtxXAo841JUmXRhROlmfWsezdoq5DiIvMW/sbomEhcNLn8t6', 'admin', 'active'),
(2, 'Jane Doe', 'user@petopia.com', '019-7569923', 'Lot G-40, Ground Floor, Mitsui Outlet Park KLIA, Persiaran Komersial, 64000 Sepang, Selangor, Malaysia.', '$2y$10$l/FZxyabHxeDTxaFcDehCuEV3IGG/hZPzgtM.9yLVHuQI292Hll.y', 'user', 'active');

-- Seed Pets
INSERT INTO `pets` (`id`, `name`, `type`, `breed`, `age`, `status`, `personality`, `vaccination_record`, `medical_history`, `image_path`) VALUES
(1, 'Oliver', 'Dog', 'Golden Retriever', '2 years', 'Adopted', 'Energetic, friendly, loves children and playing fetch in the park.', 'Fully Vaccinated (Rabies, DHPP, Bordetella)', 'Neutered, Microchipped, De-wormed. Excellent health.', 'uploads/goldenRetriever.jpg'),
(2, 'Bella', 'Cat', 'Siamese', '1 year', 'Available', 'Affectionate, vocal, curious, and loves to cuddle in warm spots.', 'Vaccinated (FVRCP, Rabies)', 'Spayed, Microchipped. Treated for ear mites, now fully recovered.', 'uploads/siamese.jpg'),
(3, 'Coco', 'Rabbit', 'Holland Lop', '8 months', 'Available', 'Quiet, gentle, initially shy but warms up with treats and soft strokes.', 'Vaccinated (RHDV2)', 'Neutered. Healthy and active.', 'uploads/rabbit.jpg'),
(4, 'Pippin', 'Bird', 'Cockatiel', '1.5 years', 'Adopted', 'Cheerful, highly social, loves whistling tunes and sitting on shoulders.', 'N/A (Avian health clearance)', 'Healthy. Feathers in great condition.', 'uploads/default_bird.jpg'),
(5, 'Max', 'Dog', 'Beagle', '3 years', 'Available', 'Loyal, scent-driven, friendly, and great with other dogs.', 'Fully Vaccinated (Rabies, DHPP)', 'Neutered. Mild food allergies, managed with a special diet.', 'uploads/beagle.jpg'),
(6, 'Luna', 'Cat', 'Persian', '4 years', 'Available', 'Calm, quiet, enjoys lounging in sunny spots and being groomed.', 'Vaccinated (FVRCP, Rabies)', 'Spayed. Requires daily brushing for her long coat.', 'uploads/default_cat.jpg');

-- Seed Applications
INSERT INTO `applications` (`id`, `user_id`, `pet_id`, `name`, `email`, `phone`, `address`, `pickup_datetime`, `has_experience`, `remarks`, `status`, `created_at`) VALUES
(1, 2, 1, 'Jane Doe', 'user@petopia.com', '019-7569923', 'Lot G-40, Ground Floor, Mitsui Outlet Park KLIA, Persiaran Komersial, 64000 Sepang, Selangor, Malaysia.', '2026-07-24 09:45:00', 0, '', 'Approved', '2026-07-11 10:15:00'),
(2, 2, 2, 'Jane Doe', 'user@petopia.com', '019-7569923', 'Lot G-40, Ground Floor, Mitsui Outlet Park KLIA, Persiaran Komersial, 64000 Sepang, Selangor, Malaysia.', '2026-07-16 17:56:00', 1, '', 'Pending', '2026-07-11 11:20:00'),
(3, 2, 3, 'Jane Doe', 'user@petopia.com', '019-7569923', 'Lot G-40, Ground Floor, Mitsui Outlet Park KLIA, Persiaran Komersial, 64000 Sepang, Selangor, Malaysia.', '2026-06-17 15:20:00', 1, '', 'Rejected', '2026-06-11 09:30:00'),
(4, 2, 4, 'Jane Doe', 'user@petopia.com', '019-7569923', 'Lot G-40, Ground Floor, Mitsui Outlet Park KLIA, Persiaran Komersial, 64000 Sepang, Selangor, Malaysia.', '2026-07-11 10:00:00', 1, '', 'Approved', '2026-07-11 08:00:00');

-- Seed Wishlists
INSERT INTO `wishlists` (`user_id`, `pet_id`) VALUES
(2, 2),
(2, 5);

-- Seed Blogs
INSERT INTO `blogs` (`id`, `title`, `content`, `image_path`, `hashtags`, `category`, `title_font_size`, `title_font_color`) VALUES
(1, 'Welcoming Your New Pet Home', 'Bringing a new pet home is an exciting milestone! To make the transition as smooth as possible, establish a quiet space for them, buy essential supplies beforehand (like food, leash, and crate), and set a routine from day one. Remember that patience is key - it can take several weeks for an animal to fully adjust to their new environment.', 'uploads/welcomeBlog.jpg', '#New #Announcement', 'Pet Care', 24, '#333333'),
(2, 'Importance of Pet Vaccinations', 'Vaccinations are essential to protect your furry friends from severe, preventable illnesses like rabies, parvovirus, and feline leukemia. Keeping track of their immunization schedules and consulting your vet regularly is one of the most critical aspects of pet ownership. Up-to-date vaccines ensure a long, healthy life.', 'uploads/vaccine.jpg', '#PetCare #Health', 'Medical', 24, '#ff6b6b');

-- Seed Inquiries (Service Forms)
INSERT INTO `inquiries` (`id`, `name`, `email`, `message`, `admin_reply`, `replied_at`) VALUES
(1, 'John Smith', 'john@example.com', 'Hi, I wanted to ask if you have any adoption events scheduled for this month? Thanks!', 'Hi John, yes we do! We are hosting a pet adoption drive this Saturday from 10 AM to 4 PM at our headquarters.', '2026-07-16 12:00:00'),
(2, 'Sarah Jenkins', 'sarah@example.com', 'Hello, is there an age restriction for adopting a puppy?', NULL, NULL),
(3, 'Mark Davis', 'mark@example.com', 'Do you offer pet grooming services at your facility?', NULL, NULL),
(4, 'Emily Watson', 'emily@example.com', 'I would love to volunteer at Petopia. How can I apply?', NULL, NULL),
(5, 'David Miller', 'david@example.com', 'Are all pets on your site vaccinated and microchipped?', NULL, NULL),
(6, 'Jessica Taylor', 'jessica@example.com', 'Do you take in stray animals? There is a kitten in my backyard.', NULL, NULL);

-- Seed Notifications
INSERT INTO `notifications` (`user_id`, `type`, `related_id`, `message`) VALUES
(2, 'application', 1, 'Your adoption application for Oliver has been Approved! Pickup is scheduled for 7/24/2026, 9:45 AM.'),
(2, 'inquiry', 1, 'Admin replied to your inquiry: "Hi John, yes we do! We are hosting a pet adoption drive this Saturday..."');

-- Seed CMS Content
INSERT INTO `cms_content` (`meta_key`, `meta_value`) VALUES
('homepage_hero_title', 'Find Your Pawfect Companion'),
('homepage_hero_desc', 'Welcome to Petopia, where loving hearts meet rescue pets in need of a forever home. Start your adoption journey today and change a life forever.'),
('homepage_hero_image', 'uploads/hero.jpg'),
('about_mission', 'To connect loving families with pets in need, ensuring every animal receives care, shelter, and a second chance at life.'),
('about_history', 'Founded in 2020, Petopia began as a small shelter with only ten rescue kennels. Over the years, through community support and passionate volunteers, we have grown to be one of the regions largest rescue centers, helping over 500 animals find their forever homes.'),
('about_values', 'Compassion, Integrity, Respect, Dedication, Community.'),
('contact_address', 'Pekan Batu 14 Hulu Langat, 43100 Hulu Langat, Selangor'),
('contact_phone', '+ 012-919 2263'),
('contact_email', 'support@petopia.com'),
('contact_facebook', 'https://facebook.com/petopia'),
('contact_instagram', 'https://instagram.com/petopia');
 

