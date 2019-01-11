CREATE DEFINER=`root`@`localhost` PROCEDURE `TodaySettlement`()
BEGIN
## 配置参数
DECLARE user_static_bonus numeric (10,2); ## 活期比例
DECLARE user_team_bonus numeric (10,2);   ## 业绩比例
## 用户参数
DECLARE user_uuid INT(11);
DECLARE user_current numeric (12,2);## 提速值
DECLARE user_regular numeric (12,2);## 定期值
DECLARE user_today_team_money numeric (12,2); ##团队百分比
DECLARE user_release_regular numeric (12,2); ## 提速百分比
DECLARE user_min_release numeric (12,2); ## 定期释放最低值
## 定义事务
DECLARE t_error INTEGER DEFAULT 0;
DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET t_error=1;
START TRANSACTION;
## 查询配置
SELECT value INTO user_static_bonus FROM think_config_capital WHERE name = 'static_bonus';
SELECT value INTO user_team_bonus FROM think_config_capital WHERE name = 'team_bonus';
SELECT value INTO user_min_release FROM think_config_capital WHERE name = 'min_release';
## 添加提速释放值
INSERT  INTO  think_money_log (uuid,money,original,type,state,info,add_time,classify)
SELECT m.uuid,(m.current*user_static_bonus/100) as money,m.current,4,1,'提速值释放定期' as info,unix_timestamp(now()) as add_time ,7 FROM
think_money as m INNER JOIN think_member as u ON u.uuid = m.uuid WHERE m.current >= 1 AND u.status = 1 AND u.is_proving = 1 AND u.activation = 1 AND m.regular > user_min_release;
## 添加今日团队业绩 记录
INSERT  INTO  think_money_log (uuid,money,original,type,state,info,add_time,classify)
SELECT m.uuid,(m.today_team_money*user_team_bonus/100) as money,m.today_team_money,4,1,'今日团队业绩释放定期' as info,unix_timestamp(now()) as add_time ,8 FROM
think_money as m INNER JOIN think_member as u ON u.uuid = m.uuid WHERE  m.today_team_money > 0  AND u.status = 1 AND u.is_proving = 1 AND u.activation = 1 AND m.regular > user_min_release;
## 只修改 提速值大于1000的
UPDATE think_money SET release_regular = release_regular + (current*user_static_bonus/100) + (today_team_money*user_team_bonus/100),today_team_money=0 WHERE regular > user_min_release;
## 清理掉今日不符合释放条件的释放值
UPDATE think_money AS m JOIN think_member as u ON m.uuid = u.uuid SET m.release_regular = 0,m.today_team_money=0 WHERE m.regular <= user_min_release AND u.status <> 1 AND  u.is_proving <> 1 AND u.activation <> 1;
## 验证事务
   IF t_error = 1 THEN
      ROLLBACK;
      ## 添加 释放异常用户记录 uuid/ 释放的金额 时间
   ELSE
      COMMIT;
   END IF;
END;
