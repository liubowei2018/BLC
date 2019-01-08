CREATE DEFINER=`root`@`localhost` PROCEDURE `TeamPerformance`( IN member_id int(11),IN member_money numeric (12,2))
BEGIN
##用户信息参数   增加团队奖金,今日团队奖金
DECLARE user_pid int (11);## 父级id
DECLARE user_id int (11);##自身id
DECLARE user_uuid int (11);
DECLARE user_number int(11);## 一二代不发放
## 账户没有激活 没有认证 不发放团队奖
DECLARE user_team_state int(11);
DECLARE user_is_proving int(11);
DECLARE user_activation int(11);
## 查询配置信息
DECLARE user_count INT (11);
DECLARE config_team_direct_push INT(11);
## 查询用户是否存在
SET user_number = 1;
SELECT pid INTO user_pid FROM think_member WHERE id = member_id;
SELECT value INTO config_team_direct_push FROM think_config_capital WHERE name = 'team_direct_push'; ## 直推人数
WHILE user_pid <> 0 DO
  SELECT pid,id,uuid,activation,is_proving,team_state INTO user_pid,user_id,user_uuid,user_activation,user_is_proving,user_team_state FROM think_member WHERE id = user_pid;
  ## 直接推荐人数
  SELECT count(*) INTO user_count FROM think_member WHERE pid = user_id AND is_proving = 1 AND activation = 1;
  ##增加团队业绩和今日业绩  直推够  团队激活  实名认证  第三轮进行增加
   IF user_number > 2  AND user_count >= config_team_direct_push AND user_team_state = 1 AND user_is_proving = 1 THEN
          UPDATE think_member SET team_money = team_money + member_money WHERE id = user_id;
          UPDATE think_money SET today_team_money = today_team_money + member_money WHERE uuid = user_uuid;
    END IF;
  SET user_number = user_number + 1;
  SET user_count = 0;
END WHILE;
END;

