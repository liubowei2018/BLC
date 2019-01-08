CREATE PROCEDURE `GetReleaseToday`(`user_uuid` int(11))
BEGIN
## 用户信息
DECLARE user_regular numeric (12,2);##定期
DECLARE user_increment numeric (12,2);##增值
DECLARE user_abc_coin numeric (12,2);##可用
DECLARE user_release_regular numeric (12,2);##今日释放值
## 配置信息
DECLARE config_min_release INT (11);## 释放定期最小值
DECLARE is_end INT (11);## 结束循环
## 定义事务
DECLARE t_error INTEGER DEFAULT 0;
DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET t_error=1;
START TRANSACTION;
## 查询配置信息
SELECT value INTO config_min_release FROM think_config_capital WHERE nane = 'min_release';
SET is_end = 1;
## 循环查询用户资金情况
WHILE is_end = 1 DO
## 查询用户资金
SELECT regular,increment,abc_coin,release_regular INTO user_regular,user_increment,user_abc_coin,user_release_regular FROM think_money WHERE uuid = user_uuid;
IF (user_regular <= config_min_release) OR user_release_regular = 0 THEN
## 如果定期小于等于配置值 结束释放
SET is_end = 0;
ELSEIF (user_increment >= user_release_regular) THEN
## 如果增值大于释放值  增值释放所有释放值  1000 > 100
UPDATE think_money SET increment = increment - user_release_regular ,abc_coin = abc_coin + user_release_regular ,release_regular = 0 WHERE uuid = user_uuid;
INSERT  INTO  think_money_log (uuid,money,original,type,state,info,add_time,classify) VALUE
(user_uuid,user_release_regular,user_increment,5,2,'晚间释放增值',unix_timestamp(now()),9);
INSERT  INTO  think_money_log (uuid,money,original,type,state,info,add_time,classify) VALUE
(user_uuid,user_release_regular,user_abc_coin,3,1,'晚间释放增值',unix_timestamp(now()),9);
SET is_end = 0;
ELSEIF (user_increment < user_release_regular) AND (user_increment > 0 ) THEN
## 如果增值小于释放值 且 不为零  增值 减去所有  100 < 1000
UPDATE think_money SET increment = 0 ,abc_coin = abc_coin + user_increment,release_regular = release_regular - user_increment WHERE uuid = user_uuid;
INSERT  INTO  think_money_log (uuid,money,original,type,state,info,add_time,classify) VALUE
(user_uuid,user_increment,user_increment,5,2,'晚间释放增值',unix_timestamp(now()),9);
INSERT  INTO  think_money_log (uuid,money,original,type,state,info,add_time,classify) VALUE
(user_uuid,user_increment,user_abc_coin,3,1,'晚间释放增值',unix_timestamp(now()),9);

ELSEIF (user_regular >= (user_release_regular+config_min_release)) AND user_increment = 0 THEN
## 如果定期大于 释放值+配置中最小值 那就直接减完  1000 > 200+100

UPDATE think_money SET regular = regular - user_release_regular, abc_coin = abc_coin + user_release_regular,release_regular = 0 WHERE uuid = user_uuid;
INSERT  INTO  think_money_log (uuid,money,original,type,state,info,add_time,classify) VALUE
(user_uuid,user_release_regular,user_regular,1,2,'晚间释放定期',unix_timestamp(now()),9);
INSERT  INTO  think_money_log (uuid,money,original,type,state,info,add_time,classify) VALUE
(user_uuid,user_release_regular,user_abc_coin,3,1,'晚间释放增值',unix_timestamp(now()),9);

ELSEIF (user_regular < (user_release_regular+config_min_release)) AND user_increment = 0 THEN
## 如果定期小于 释放值+配置中最小值  定期值只保留同等配置值 150 < 200+100

UPDATE think_money SET regular = config_min_release, abc_coin = abc_coin + (user_regular-config_min_release),release_regular = 0 WHERE uuid = user_uuid;
INSERT  INTO  think_money_log (uuid,money,original,type,state,info,add_time,classify) VALUE
(user_uuid,(user_regular-config_min_release),user_regular,1,2,'晚间释放定期',unix_timestamp(now()),9);
INSERT  INTO  think_money_log (uuid,money,original,type,state,info,add_time,classify) VALUE
(user_uuid,(user_regular-config_min_release),user_abc_coin,3,1,'晚间释放增值',unix_timestamp(now()),9);

ELSE
## 结束释放
  SET is_end = 0;
END IF;
END WHILE;

## 验证事务
   IF t_error = 1 THEN
      ROLLBACK;
   ELSE
      COMMIT;
   END IF;
END;