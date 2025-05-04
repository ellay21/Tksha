--sample users
INSERT INTO users (name, email, password, gender, birthdate, age, bio, location, profile_completed, created_at)
VALUES 
('John Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'male', '1990-01-15', 33, 'I love hiking and traveling', 'New York, USA', 1, NOW()),
('Jane Smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'female', '1992-05-20', 31, 'I enjoy reading and cooking', 'Los Angeles, USA', 1, NOW()),
('Michael Johnson', 'michael@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'male', '1988-11-10', 35, 'Sports enthusiast and tech lover', 'Chicago, USA', 1, NOW()),
('Emily Davis', 'emily@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'female', '1995-03-25', 28, 'Art lover and coffee addict', 'San Francisco, USA', 1, NOW());

--sample interests
INSERT INTO user_interests (user_id, interest)
VALUES 
(1, 'movies'), (1, 'travel'), (1, 'hiking'), (1, 'music'),
(2, 'reading'), (2, 'cooking'), (2, 'music'), (2, 'art'),
(3, 'sports'), (3, 'technology'), (3, 'gaming'), (3, 'movies'),
(4, 'art'), (4, 'photography'), (4, 'music'), (4, 'fashion');
