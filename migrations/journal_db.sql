-- journal_db.sql
-- Fully compatible with MariaDB

-- Create database (with clean setup)
DROP DATABASE IF EXISTS journal_db;
CREATE DATABASE IF NOT EXISTS journal_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE journal_db;

-- Users table
CREATE TABLE users (
                       id INT AUTO_INCREMENT PRIMARY KEY,
                       username VARCHAR(50) UNIQUE NOT NULL,
                       email VARCHAR(100) UNIQUE NOT NULL,
                       password VARCHAR(255) NOT NULL,
                       avatar VARCHAR(255) DEFAULT NULL,
                       bio TEXT DEFAULT NULL,
                       last_login DATETIME DEFAULT NULL,
                       created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                       updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                       INDEX idx_email (email),
                       INDEX idx_username (username)
) ENGINE=InnoDB;

-- Password resets table
CREATE TABLE password_resets (
                                 id INT AUTO_INCREMENT PRIMARY KEY,
                                 email VARCHAR(100) NOT NULL,
                                 token VARCHAR(64) NOT NULL,
                                 expires_at DATETIME NOT NULL,
                                 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                 INDEX idx_token (token),
                                 INDEX idx_email (email),
                                 FOREIGN KEY (email) REFERENCES users(email) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Remember tokens table
CREATE TABLE remember_tokens (
                                 id INT AUTO_INCREMENT PRIMARY KEY,
                                 user_id INT NOT NULL,
                                 token VARCHAR(64) NOT NULL,
                                 expires_at DATETIME NOT NULL,
                                 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                 INDEX idx_token (token),
                                 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Prompts table
CREATE TABLE prompts (
                         id INT AUTO_INCREMENT PRIMARY KEY,
                         content TEXT NOT NULL,
                         category VARCHAR(50) DEFAULT NULL,
                         author VARCHAR(50) DEFAULT 'system',
                         created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                         INDEX idx_category (category)
) ENGINE=InnoDB;

-- User prompts assignment table
CREATE TABLE user_prompts (
                              id INT AUTO_INCREMENT PRIMARY KEY,
                              user_id INT NOT NULL,
                              prompt_id INT NOT NULL,
                              assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                              FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                              FOREIGN KEY (prompt_id) REFERENCES prompts(id) ON DELETE CASCADE,
                              UNIQUE KEY unique_user_prompt (user_id, prompt_id, assigned_at),
                              INDEX idx_user_date (user_id, assigned_at)
) ENGINE=InnoDB;

-- Journal entries table
CREATE TABLE journal_entries (
                                 id INT AUTO_INCREMENT PRIMARY KEY,
                                 user_id INT NOT NULL,
                                 title VARCHAR(255) NOT NULL,
                                 content TEXT NOT NULL,
                                 prompt_id INT DEFAULT NULL,
                                 mood TINYINT DEFAULT NULL CHECK (mood BETWEEN 1 AND 5),
                                 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                                 updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                                 FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                                 FOREIGN KEY (prompt_id) REFERENCES prompts(id) ON DELETE SET NULL,
                                 INDEX idx_user_created (user_id, created_at),
                                 INDEX idx_prompt (prompt_id)
) ENGINE=InnoDB;

-- Tags table
CREATE TABLE tags (
                      id INT AUTO_INCREMENT PRIMARY KEY,
                      name VARCHAR(50) NOT NULL,
                      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                      UNIQUE KEY unique_name (name)
) ENGINE=InnoDB;

-- Entry tags relationship table
CREATE TABLE entry_tags (
                            entry_id INT NOT NULL,
                            tag_id INT NOT NULL,
                            PRIMARY KEY (entry_id, tag_id),
                            FOREIGN KEY (entry_id) REFERENCES journal_entries(id) ON DELETE CASCADE,
                            FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE,
                            INDEX idx_tag (tag_id)
) ENGINE=InnoDB;

-- Mood entries table
CREATE TABLE mood_entries (
                              id INT AUTO_INCREMENT PRIMARY KEY,
                              user_id INT NOT NULL,
                              mood TINYINT NOT NULL CHECK (mood BETWEEN 1 AND 5),
                              entry_id INT DEFAULT NULL,
                              notes TEXT DEFAULT NULL,
                              created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                              updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                              FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                              FOREIGN KEY (entry_id) REFERENCES journal_entries(id) ON DELETE SET NULL,
                              UNIQUE KEY unique_user_date (user_id, created_at),
                              INDEX idx_user_created (user_id, created_at),
                              INDEX idx_mood (mood)
) ENGINE=InnoDB;

-- Habits table
CREATE TABLE habits (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        name VARCHAR(50) NOT NULL,
                        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                        UNIQUE KEY unique_user_habit (user_id, name)
) ENGINE=InnoDB;

-- Habit entries table
CREATE TABLE habit_entries (
                               id INT AUTO_INCREMENT PRIMARY KEY,
                               habit_id INT NOT NULL,
                               user_id INT NOT NULL,
                               entry_date DATE NOT NULL,
                               completed BOOLEAN DEFAULT TRUE,
                               notes TEXT DEFAULT NULL,
                               created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                               FOREIGN KEY (habit_id) REFERENCES habits(id) ON DELETE CASCADE,
                               FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                               UNIQUE KEY unique_user_habit_date (user_id, habit_id, entry_date),
                               INDEX idx_habit_date (habit_id, entry_date)
) ENGINE=InnoDB;

-- System settings table
CREATE TABLE settings (
                          id INT AUTO_INCREMENT PRIMARY KEY,
                          setting_key VARCHAR(50) UNIQUE NOT NULL,
                          setting_value TEXT NOT NULL,
                          description TEXT DEFAULT NULL,
                          updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================
-- SAMPLE DATA INSERTIONS
-- =============================================

-- Insert sample prompts
INSERT INTO prompts (content, category) VALUES
                                            ('What are you grateful for today?', 'gratitude'),
                                            ('Describe a challenge you faced and how you handled it', 'reflection'),
                                            ('What are your goals for this week?', 'planning'),
                                            ('Write about someone who inspired you recently', 'relationships'),
                                            ('What did you learn today?', 'learning'),
                                            ('Describe your perfect day', 'imagination'),
                                            ('What are you looking forward to?', 'future'),
                                            ('Write about a childhood memory', 'nostalgia');

-- Insert sample user (password: "password123")
INSERT INTO users (username, email, password) VALUES
    ('journaluser', 'user@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insert sample journal entries
INSERT INTO journal_entries (user_id, title, content, prompt_id, created_at) VALUES
                                                                                 (1, 'First Entry', 'Today I started my journal. Excited for this journey!', 1, NOW() - INTERVAL 2 DAY),
                                                                                 (1, 'Weekly Goals', '1. Finish project\n2. Exercise 3 times\n3. Read 50 pages', 3, NOW() - INTERVAL 1 DAY),
                                                                                 (1, 'Today''s Challenge', 'Had a difficult conversation but resolved it well', 2, NOW()),
                                                                                 (1, 'Inspiration', 'My mentor showed me how to handle pressure gracefully', 4, NOW() - INTERVAL 3 DAY);

-- Insert sample tags
INSERT INTO tags (name) VALUES
                            ('happy'), ('productive'), ('reflective'), ('challenging'), ('growth'), ('emotional'), ('creative');

-- Insert entry-tag relationships
INSERT INTO entry_tags (entry_id, tag_id) VALUES
                                              (1, 1), (1, 5),
                                              (2, 2), (2, 5),
                                              (3, 3), (3, 4),
                                              (4, 1), (4, 6);

-- Insert mood entries
INSERT INTO mood_entries (user_id, mood, entry_id, notes, created_at) VALUES
                                                                          (1, 4, 1, 'Felt excited starting the journal', NOW() - INTERVAL 2 DAY),
                                                                          (1, 3, NULL, 'Average day', NOW() - INTERVAL 4 DAY),
                                                                          (1, 5, 3, 'Proud of handling the challenge well', NOW()),
                                                                          (1, 4, 4, 'Inspired after mentoring session', NOW() - INTERVAL 3 DAY);

-- Insert sample habits
INSERT INTO habits (user_id, name) VALUES
                                       (1, 'Morning meditation'),
                                       (1, 'Daily exercise'),
                                       (1, 'Reading');

-- Insert habit entries
INSERT INTO habit_entries (habit_id, user_id, entry_date, completed) VALUES
                                                                         (1, 1, CURDATE(), TRUE),
                                                                         (2, 1, CURDATE(), FALSE),
                                                                         (3, 1, CURDATE(), TRUE),
                                                                         (1, 1, CURDATE() - INTERVAL 1 DAY, TRUE),
                                                                         (2, 1, CURDATE() - INTERVAL 1 DAY, TRUE);

-- Insert system settings
INSERT INTO settings (setting_key, setting_value, description) VALUES
                                                                   ('daily_reminder_time', '20:00', 'Time to send daily journal reminders'),
                                                                   ('theme', 'light', 'Default color theme for the application'),
                                                                   ('entries_per_page', '10', 'Number of entries to show per page');