CREATE DEFINER=`root`@`localhost` PROCEDURE `TodaySettlement`()
BEGIN
## 配置参数
DECLARE user_static_bonus numeric (10,2); ## 活期比例
DECLARE user_team_bonus numeric (10,2);   ## 业绩比例
DECLARE user_today_team_money numeric (12,2); ##团队百分比
DECLARE user_release_regular numeric (12,2); ## 提速百分比
DECLARE user_min_release numeric (12,2); ## 定期释放最低值
## 用户参数
DECLARE user_uuid INT(11);
DECLARE user_regular numeric (12,2);## 定期值
DECLARE user_increment numeric (12,2);## 增值
DECLARE user_current numeric (12,2);## 提速值
DECLARE user_abc_coin numeric (12,2);## 可用值
DECLARE user_today_team_money numeric (12,2);## 定期值
DECLARE user_release_regular numeric (12,2);## 定期值
DECLARE user_frozen_push numeric (12,2);## 定期值
DECLARE user_frozen_indirect numeric (12,2);## 定期值
DECLARE user_freeze_team numeric (12,2);## 定期值
DECLARE user_freeze_yeji numeric (12,2);## 定期值
## 用户状态
DECLARE user_team_state INT(11);
## 定义事务
DECLARE t_error INTEGER DEFAULT 0;
DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET t_error=1;
## 定义游标
DECLARE cursor_num INT DEFAULT 0;
## 定义游标,会员账号  未冻结  已激活 进行释放积分
DECLARE company_list CURSOR FOR SELECT  m.uuid,m.regular,m.increment,m.current,m.abc_coin,m.today_team_money,m.release_regular,m.frozen_push,m.frozen_indirect,m.freeze_team,m.freeze_yeji,u.team_state FROM think_money as m INNER JOIN think_member as u ON m.uuid = u.uuid WHERE u.status = 1 AND u.is_proving = 1 AND u.activation = 1 ;
## 声明当游标遍历完全部记录后将标志变量置成某个值
DECLARE CONTINUE HANDLER FOR NOT FOUND SET cursor_num=1;
##开启事务
START TRANSACTION;
## 更新配置信息







## 开启游标
OPEN company_list;
FETCH company_list INTO user_uuid,user_regular,user_increment,user_current,user_abc_coin,user_today_team_money,user_release_regular,user_frozen_push,user_frozen_indirect,user_freeze_team,user_freeze_yeji,user_team_state;
WHILE cursor_num <> 1 DO
## 首先验证 用户资金发否符合发放最低值




FETCH company_list INTO user_uuid,user_regular,user_increment,user_current,user_abc_coin,user_today_team_money,user_release_regular,user_frozen_push,user_frozen_indirect,user_freeze_team,user_freeze_yeji,user_team_state;
END WHILE;
## 关闭游标
CLOSE company_list;

## 验证事务
   IF t_error = 1 THEN
      ROLLBACK;
      ## 添加 释放异常用户记录 uuid/ 释放的金额 时间
   ELSE
      COMMIT;
   END IF;
END;
