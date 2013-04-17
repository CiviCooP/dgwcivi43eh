CREATE  TABLE `dgw_config` (
  `id` INT NOT NULL AUTO_INCREMENT ,
  `label` VARCHAR(128) NULL ,
  `value` VARCHAR(256) NULL ,
  PRIMARY KEY (`id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;
