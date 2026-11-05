CREATE DATABASE IF NOT EXISTS customshirtgen;
USE customshirtgen;

CREATE TABLE IF NOT EXISTS `enquiries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `product_type` varchar(100) NOT NULL,
  `base_color` varchar(50) NOT NULL,
  `design_method` varchar(50) DEFAULT 'embroidery',
  `total_quantity` int(11) NOT NULL DEFAULT 0,
  `design_image_path` varchar(255) DEFAULT NULL,
  `logo_image_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `enquiry_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `enquiry_id` int(11) NOT NULL,
  `size` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `enquiry_id` (`enquiry_id`),
  CONSTRAINT `enquiry_items_ibfk_1` FOREIGN KEY (`enquiry_id`) REFERENCES `enquiries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `blog_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) NOT NULL UNIQUE,
  `title` varchar(255) NOT NULL,
  `category` varchar(50) DEFAULT 'Business',
  `excerpt` text DEFAULT NULL,
  `content` longtext NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `blog_posts` (`slug`, `title`, `category`, `excerpt`, `content`, `image_url`) VALUES
('recycled-embroidery-threads', 'The Move to Sustainable, Recycled Threads', 'Sustainability', 'How modern textile finishers are reducing their carbon footprint by sourcing brilliant, durable threads made entirely from recycled ocean plastics.', '<p>Embroidery has come a long way. Recycled thread from post-consumer PET bottles offers exactly the same tear resistance and color fastness as classic polyester thread, while saving up to 60% in energy during manufacturing.</p>', 'https://placehold.co/600x400/f1f5f9/64748b?text=Sustainable+Threads'),
('dtg-vs-screen-print', 'DTG vs Screen Print: Which is Right?', 'Printing', 'Understanding the core differences between Direct-to-Garment and traditional screen printing to maximize quality and minimize costs for your next event.', '<p>When comparing DTG and Screen Print, volume is key. For orders under 50 pieces, the setup fees of screen printing make DTG the undisputed king. For 500 pieces, screen printing is dramatically more cost-efficient.</p>', 'https://placehold.co/600x400/f1f5f9/64748b?text=DTG+Printing'),
('impact-of-premium-uniforms', 'The Impact of Premium Uniforms', 'Business', 'Why investing in high-end, cleanly embroidered polos for your staff yields a massive return on investment in B2B client trust.', '<p>Premium uniforms speak volumes about your brand. Studies show a 34% increase in perceived employee competence when staff are wearing branded, well-fitted corporate apparel instead of casual wear.</p>', 'https://placehold.co/600x400/f1f5f9/64748b?text=Corporate+Apparel'),
('logo-digitization-art', 'The Art of Digitizing Logos', 'Embroidery', 'An inside look at how our punchers manually map out every single needle drop to ensure your flat logo looks incredible in 3D.', '<p>The process of digitizing is highly complex. It isn\'t just scanning an image; a human puncher has to identify thread direction, underlays, and push/pull compensation to ensure the logo doesn\'t pucker the fabric.</p>', 'https://placehold.co/600x400/f1f5f9/64748b?text=Logo+Digitization');
