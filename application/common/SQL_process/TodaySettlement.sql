CREATE DEFINER=`root`@`localhost` PROCEDURE `TodaySettlement`() ## 异常定义顺序
BEGIN
## 配置参数
DECLARE user_directpush_number INT (11); ## 直推人数
DECLARE user_indirect_number INT (11); ## 间接人数
DECLARE user_team_direct_push INT (11); ## 团队-直推人数
DECLARE user_static_bonus numeric (10,2); ## 活期比例
DECLARE user_team_bonus numeric (10,2);   ## 业绩比例
DECLARE user_min_release numeric (12,2); ## 定期释放最低值
## 用户参数
DECLARE user_id INT(11);
DECLARE user_uuid INT(11);
DECLARE user_regular numeric (12,2);## 定期值
DECLARE user_increment numeric (12,2);## 增值
DECLARE user_current numeric (12,2);## 提速值
DECLARE user_abc_coin numeric (12,2);## 可用值
DECLARE user_today_team_money numeric (12,2);## 今日业绩
DECLARE user_release_regular numeric (12,2);## 今日释放
DECLARE user_frozen_push numeric (12,2);## 冻结直推奖励
DECLARE user_frozen_indirect numeric (12,2);## 冻结间接奖励
DECLARE user_freeze_team numeric (12,2);## 冻结团队业绩
DECLARE user_freeze_yeji numeric (12,2);## 冻结总业绩
DECLARE total_release_number numeric (12,2); ## 累计今日释放总数
## 用户状态
DECLARE user_push_count INT(11);## 用户直推
DECLARE user_team_state INT(11); ## 是否开启团队
## 定义事务
DECLARE t_error INTEGER DEFAULT 0;
DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET t_error=1;
## 定义游标
DECLARE cursor_num INT DEFAULT 0;
## 定义游标,会员账号  未冻结  已激活 进行释放积分
DECLARE company_list CURSOR FOR SELECT  m.id,m.uuid,m.regular,m.increment,m.current,m.abc_coin,m.today_team_money,m.release_regular,m.frozen_push,m.frozen_indirect,m.freeze_team,m.freeze_yeji,u.team_state FROM think_money as m INNER JOIN think_member as u ON m.uuid = u.uuid WHERE u.status = 1 AND u.is_proving = 1 AND u.activation = 1 ;
## 声明当游标遍历完全部记录后将标志变量置成某个值
DECLARE CONTINUE HANDLER FOR NOT FOUND SET cursor_num=1;
##开启事务
START TRANSACTION;
## 更新配置信息

## 最低定期配置
SELECT min_release INTO user_min_release FROM think_config_capital WHERE name = 'min_release';
## 提速配置
SELECT static_bonus INTO user_static_bonus FROM think_config_capital WHERE name = 'static_bonus';
## 今日业绩配置
SELECT team_bonus INTO user_team_bonus FROM think_config_capital WHERE name = 'team_bonus';
## 直推人数
SELECT directpush_number INTO user_directpush_number FROM think_config_capital WHERE name = 'directpush_number';
## 间接人数
SELECT indirect_number INTO user_indirect_number FROM think_config_capital WHERE name = 'indirect_number';
## 团队-直推人数
SELECT team_direct_push INTO user_team_direct_push FROM think_config_capital WHERE name = 'team_direct_push';

## 开启游标
OPEN company_list;
FETCH company_list INTO user_id,user_uuid,user_regular,user_increment,user_current,user_abc_coin,user_today_team_money,user_release_regular,user_frozen_push,user_frozen_indirect,user_freeze_team,user_freeze_yeji,user_team_state;
    WHILE cursor_num <> 1 DO
    ## 首先验证 用户资金发否符合发放最低值
        IF user_regular > min_release THEN
              SELECT count(*) INTO user_push_count FROM think_member WHERE pid = user_id AND is_proving = 1 AND activation = 1;
              SET total_release_number = 0;
            ## 已开启团队
            IF user_team_state = 1 AND user_push_count >= user_team_direct_push THEN
              SET total_release_number = (user_freeze_yeji + user_today_team_money) / user_team_bonus;
              UPDATE think_money SET today_team_money = 0,freeze_team = 0 WHERE  uuid = user_uuid;
            END IF;
            ## 是否发放间接
            IF user_push_count >= user_indirect_number THEN
              SET total_release_number = total_release_number + user_frozen_indirect ;
              UPDATE think_money SET frozen_indirect = 0 WHERE  uuid = user_uuid;
            END IF;
            ## 是否发放直接
            IF user_push_count >= user_directpush_number THEN
              SET total_release_number = total_release_number + user_frozen_push ;
              UPDATE think_money SET frozen_push = 0 WHERE  uuid = user_uuid;
            END IF;
            ## 计算提速值
            SET total_release_number = total_release_number + (user_current*user_static_bonus);
            UPDATE think_money SET release_regular = release_regular + total_release_number WHERE uuid = user_uuid;
        END IF;
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
