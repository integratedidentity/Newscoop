CREATE TABLE IF NOT EXISTS `weather_stat` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `location_id` varchar(50) NOT NULL DEFAULT 0,
    `location_type` varchar(50) NOT NULL,
    `location_name` varchar(255) NOT NULL,
    `location_list` varchar(255) NOT NULL,
    `region_name` varchar(255) DEFAULT NULL,
    `hour` int(5) NOT NULL,
    `symbol` int(5),
    `temperature` int(5),
    `temperature_min` int(5),
    `temperature_max` int(5),
    `precip` int(5),
    `winddir` int(5),
    `windforce` int(5),
    `snow_condition` varchar(255),
    `slope_condition` varchar(255),
    `total_slopes` int(5),
    `open_slopes` int(5),
    `time_updated` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
    PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
