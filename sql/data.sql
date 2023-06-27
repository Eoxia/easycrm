-- Copyright (C) 2022-2023 EVARISK <technique@evarisk.com>
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see https://www.gnu.org/licenses/.

-- 1.1.0

INSERT INTO `llx_c_status_propal` (`rowid`, `entity`, `ref`, `label`, `description`, `active`) VALUES(1, 0, 'Received', 'Received', '', 1);
INSERT INTO `llx_c_status_propal` (`rowid`, `entity`, `ref`, `label`, `description`, `active`) VALUES(2, 0, 'Sent', 'Sent', '', 1);
INSERT INTO `llx_c_status_propal` (`rowid`, `entity`, `ref`, `label`, `description`, `active`) VALUES(3, 0, 'OnHold', 'OnHold', '', 1);
INSERT INTO `llx_c_status_propal` (`rowid`, `entity`, `ref`, `label`, `description`, `active`) VALUES(4, 0, 'Reminder1', 'Reminder1', '', 1);
INSERT INTO `llx_c_status_propal` (`rowid`, `entity`, `ref`, `label`, `description`, `active`) VALUES(5, 0, 'Reminder2', 'Reminder2', '', 1);
INSERT INTO `llx_c_status_propal` (`rowid`, `entity`, `ref`, `label`, `description`, `active`) VALUES(6, 0, 'Reminder3', 'Reminder3', '', 1);
INSERT INTO `llx_c_status_propal` (`rowid`, `entity`, `ref`, `label`, `description`, `active`) VALUES(7, 0, 'Abandoned', 'Abandoned', '', 1);
INSERT INTO `llx_c_status_propal` (`rowid`, `entity`, `ref`, `label`, `description`, `active`) VALUES(8, 0, 'Unreachable', 'Unreachable', '', 1);
INSERT INTO `llx_c_status_propal` (`rowid`, `entity`, `ref`, `label`, `description`, `active`) VALUES(9, 0, 'ToCallBack', 'ToCallBack', '', 1);

INSERT INTO `llx_c_refusal_reason` (`rowid`, `entity`, `ref`, `label`, `description`, `active`) VALUES(1, 0, 'TooHard', 'TooHard', '', 1);
INSERT INTO `llx_c_refusal_reason` (`rowid`, `entity`, `ref`, `label`, `description`, `active`) VALUES(2, 0, 'Interface', 'Interface', '', 1);
INSERT INTO `llx_c_refusal_reason` (`rowid`, `entity`, `ref`, `label`, `description`, `active`) VALUES(3, 0, 'TooCheap', 'TooCheap', '', 1);
INSERT INTO `llx_c_refusal_reason` (`rowid`, `entity`, `ref`, `label`, `description`, `active`) VALUES(4, 0, 'TooExpensive', 'TooExpensive', '', 1);
INSERT INTO `llx_c_refusal_reason` (`rowid`, `entity`, `ref`, `label`, `description`, `active`) VALUES(5, 0, 'ToSignElsewhere', 'ToSignElsewhere', '', 1);
INSERT INTO `llx_c_refusal_reason` (`rowid`, `entity`, `ref`, `label`, `description`, `active`) VALUES(6, 0, 'NotAProjectAnymore', 'NotAProjectAnymore', '', 1);
INSERT INTO `llx_c_refusal_reason` (`rowid`, `entity`, `ref`, `label`, `description`, `active`) VALUES(7, 0, 'GoesBackOnExcel', 'GoesBackOnExcel', '', 1);
INSERT INTO `llx_c_refusal_reason` (`rowid`, `entity`, `ref`, `label`, `description`, `active`) VALUES(8, 0, 'DontWantToSay', 'DontWantToSay', '', 1);
INSERT INTO `llx_c_refusal_reason` (`rowid`, `entity`, `ref`, `label`, `description`, `active`) VALUES(9, 0, 'Other', 'Other', '', 1);
