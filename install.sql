CREATE TABLE IF NOT EXISTS `posts` (
  `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `nicename` varchar(100) NOT NULL,
  `summary` varchar(255) NOT NULL,
  `content` text,
  `published` tinyint(1) NOT NULL DEFAULT '0',
  `updated` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nicename` (`nicename`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

--
-- Dump dei dati per la tabella `posts`
--

INSERT INTO `posts` (`id`, `title`, `nicename`, `summary`, `content`, `published`, `updated`) VALUES
(1, 'My first post', 'my-first-post', 'This is my first post', 'This is my **first post**. Very beautiful!', 1, '2014-01-01 12:30:00'),
(2, 'My second post', 'my-second-post', 'This is my second post', 'This is my **second post**. Very amazing!', 1, '2014-03-01 18:30:00'),
(3, 'My 3rd post', 'post-3-updated-1', 'This is my 3rd post. Maybe the best one ever!!!', 'This is my *third post*. I''m very **proud** of it, of course.', 0, '2014-04-22 12:56:47');
