CREATE TABLE cjaycontent (
    id        INT(5) UNSIGNED      NOT NULL AUTO_INCREMENT,
    title     VARCHAR(150)         NOT NULL DEFAULT '',
    type      TINYINT(10) UNSIGNED NOT NULL DEFAULT '0',
    design    TINYINT(4) UNSIGNED  NOT NULL DEFAULT '0',
    hide      TINYINT(4) UNSIGNED  NOT NULL DEFAULT '0',
    adress    VARCHAR(255)                  DEFAULT NULL,
    comment   VARCHAR(255)                  DEFAULT NULL,
    content   TEXT                 NOT NULL,
    submitter VARCHAR(255)                  DEFAULT NULL,
    date      INT(10) UNSIGNED     NOT NULL DEFAULT '0',
    image     VARCHAR(255)                  DEFAULT NULL,
    hits      INT(6) UNSIGNED      NOT NULL DEFAULT '0',
    weight    INT(4) UNSIGNED      NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`),
    UNIQUE KEY `index` (`id`)
)
    ENGINE = ISAM;

INSERT INTO cjaycontent
VALUES (1, 'C-JAY Contet Start', 0, 0, 1, 'DO_NOT_DELETE.php', 'DO NOT DELETE THIS FILE!!', '', '1', 1050232144, NULL, 37, 0);

