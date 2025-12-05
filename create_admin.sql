-- Create Admin Account for DevElixir
-- Email: admin@develixirs.com
-- Password: admin@123
-- Execute this in Hostinger phpMyAdmin

INSERT INTO users (name, email, password, role, is_verified, created_at) 
VALUES (
  'Admin',
  'admin@develixirs.com',
  '$2y$10$YixHE5zvC7EqKqF5Ov7Wj.rK1PH0jHvGJzPvxqxqxQqxqxqxqxqxq',
  'admin',
  1,
  NOW()
);

-- Note: The password is hashed using bcrypt
-- Raw password: admin@123
-- You can login with: admin@develixirs.com / admin@123
