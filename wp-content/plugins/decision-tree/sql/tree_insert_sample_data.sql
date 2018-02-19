USE `tree`;
/*
-- Query:
-- Date: 2017-07-18 14:01
*/
INSERT INTO `tree` (`tree_id`,`tree_slug`,`tree_content`,`tree_title`,`tree_owner`,`tree_created_by`,`tree_updated_by`) VALUES (1,'citizen','','Are You Eligible to be a US Citizen',1,1,1);

/*
-- Query:
-- Date: 2017-07-18 14:02
*/
INSERT INTO `tree_element_type` (`el_type_id`,`el_type`) VALUES (1,'column');
INSERT INTO `tree_element_type` (`el_type_id`,`el_type`) VALUES (2,'question');
INSERT INTO `tree_element_type` (`el_type_id`,`el_type`) VALUES (3,'option');
INSERT INTO `tree_element_type` (`el_type_id`,`el_type`) VALUES (4,'end');


/*
-- Query:
-- Date: 2017-07-18 14:00
*/
INSERT INTO `tree_element` (`el_id`,`tree_id`,`el_type_id`,`el_title`,`el_content`,`el_created_by`,`el_updated_by`) VALUES (1,1,1,'Main Column','',1,1);
INSERT INTO `tree_element` (`el_id`,`tree_id`,`el_type_id`,`el_title`,`el_content`,`el_created_by`,`el_updated_by`) VALUES (2,1,2,'Are you at least 18 years old?','',1,1);
INSERT INTO `tree_element` (`el_id`,`tree_id`,`el_type_id`,`el_title`,`el_content`,`el_created_by`,`el_updated_by`) VALUES (3,1,3,'Yes','',1,1);
INSERT INTO `tree_element` (`el_id`,`tree_id`,`el_type_id`,`el_title`,`el_content`,`el_created_by`,`el_updated_by`) VALUES (4,1,3,'No','',1,1);
INSERT INTO `tree_element` (`el_id`,`tree_id`,`el_type_id`,`el_title`,`el_content`,`el_created_by`,`el_updated_by`) VALUES (5,1,4,'You are elegible.','You can apply for US Citizenship if you want to.',1,1);
INSERT INTO `tree_element` (`el_id`,`tree_id`,`el_type_id`,`el_title`,`el_content`,`el_created_by`,`el_updated_by`) VALUES (6,1,4,'You are not eligible.','You will get rejected from US Citizenship if you apply right now.',1,1);
INSERT INTO `tree_element` (`el_id`,`tree_id`,`el_type_id`,`el_title`,`el_content`,`el_created_by`,`el_updated_by`) VALUES (7,1,3,'Start Over','Want to go through this decision tree again?',1,1);

/*
-- Query:
-- Date: 2017-07-18 14:01
*/
INSERT INTO `tree_element_order` (`el_order_id`,`el_id`,`el_order`) VALUES (1,3,1);
INSERT INTO `tree_element_order` (`el_order_id`,`el_id`,`el_order`) VALUES (2,4,0);
INSERT INTO `tree_element_order` (`el_order_id`,`el_id`,`el_order`) VALUES (3,7,0);
INSERT INTO `tree_element_order` (`el_order_id`,`el_id`,`el_order`) VALUES (4,1,0);
INSERT INTO `tree_element_order` (`el_order_id`,`el_id`,`el_order`) VALUES (5,2,0);


/*
-- Query:
-- Date: 2017-07-18 14:01
*/
INSERT INTO `tree_element_container` (`el_container_id`,`el_id`,`el_id_child`) VALUES (1,1,2);
INSERT INTO `tree_element_container` (`el_container_id`,`el_id`,`el_id_child`) VALUES (2,2,3);
INSERT INTO `tree_element_container` (`el_container_id`,`el_id`,`el_id_child`) VALUES (3,2,4);

INSERT INTO `tree_element_destination` (`el_destination_id`,`el_id`,`el_id_destination`) VALUES (1,3,5);
INSERT INTO `tree_element_destination` (`el_destination_id`,`el_id`,`el_id_destination`) VALUES (2,4,6);
INSERT INTO `tree_element_destination` (`el_destination_id`,`el_id`,`el_id_destination`) VALUES (3,7,2);


INSERT INTO `tree`.`tree_element` (`el_id`, `tree_id`, `el_type_id`, `el_title`, `el_created_by`, `el_updated_by`) VALUES ('8', '1', '2', 'Are you a permanent resident of the US?', '1', '1');
INSERT INTO `tree_element_order` (`el_id`,`el_order`) VALUES (8,1);
INSERT INTO `tree_element` (`el_id`,`tree_id`,`el_type_id`,`el_title`,`el_created_by`,`el_updated_by`) VALUES (9,1,3,'Yes',1,1);
INSERT INTO `tree_element` (`el_id`,`tree_id`,`el_type_id`,`el_title`,`el_created_by`,`el_updated_by`) VALUES (10,1,3,'No',1,1);
INSERT INTO `tree_element_order` (`el_id`,`el_order`) VALUES (9,1);
INSERT INTO `tree_element_order` (`el_id`,`el_order`) VALUES (10,0);
INSERT INTO `tree_element_destination` (`el_id`,`el_id_destination`) VALUES (10,6);
INSERT INTO `tree_element_destination` (`el_id`,`el_id_destination`) VALUES (9,11);
INSERT INTO `tree_element_container` (`el_id`,`el_id_child`) VALUES (8,9);
INSERT INTO `tree_element_container` (`el_id`,`el_id_child`) VALUES (8,10);

INSERT INTO `tree`.`tree_element` (`el_id`, `tree_id`, `el_type_id`, `el_title`, `el_created_by`, `el_updated_by`) VALUES ('11', '1', '2', 'Have you been issued a Permanent Resident Card?', '1', '1');
INSERT INTO `tree_element_order` (`el_id`,`el_order`) VALUES (11,2);
INSERT INTO `tree_element` (`el_id`,`tree_id`,`el_type_id`,`el_title`,`el_created_by`,`el_updated_by`) VALUES (12,1,3,'Yes',1,1);
INSERT INTO `tree_element` (`el_id`,`tree_id`,`el_type_id`,`el_title`,`el_created_by`,`el_updated_by`) VALUES (13,1,3,'No',1,1);
INSERT INTO `tree_element_order` (`el_id`,`el_order`) VALUES (12,1);
INSERT INTO `tree_element_order` (`el_id`,`el_order`) VALUES (13,0);
INSERT INTO `tree_element_destination` (`el_id`,`el_id_destination`) VALUES (13,6);
INSERT INTO `tree_element_destination` (`el_id`,`el_id_destination`) VALUES (12,14);
INSERT INTO `tree_element_container` (`el_id`,`el_id_child`) VALUES (11,12);
INSERT INTO `tree_element_container` (`el_id`,`el_id_child`) VALUES (11,13);

INSERT INTO `tree`.`tree_element` (`el_id`, `tree_id`, `el_type_id`, `el_title`, `el_created_by`, `el_updated_by`) VALUES ('14', '1', '2', 'I have been a permanent resident for...', '1', '1');
INSERT INTO `tree_element_order` (`el_id`,`el_order`) VALUES (14,3);
INSERT INTO `tree_element` (`el_id`,`tree_id`,`el_type_id`,`el_title`,`el_created_by`,`el_updated_by`) VALUES (15,1,3,'Less than three years',1,1);
INSERT INTO `tree_element` (`el_id`,`tree_id`,`el_type_id`,`el_title`,`el_created_by`,`el_updated_by`) VALUES (16,1,3,'Three or more years',1,1);
INSERT INTO `tree_element` (`el_id`,`tree_id`,`el_type_id`,`el_title`,`el_created_by`,`el_updated_by`) VALUES (17,1,3,'Five or more years',1,1);
INSERT INTO `tree_element_order` (`el_id`,`el_order`) VALUES (15,0);
INSERT INTO `tree_element_order` (`el_id`,`el_order`) VALUES (16,1);
INSERT INTO `tree_element_order` (`el_id`,`el_order`) VALUES (17,2);
INSERT INTO `tree_element_destination` (`el_id`,`el_id_destination`) VALUES (15,6);
INSERT INTO `tree_element_destination` (`el_id`,`el_id_destination`) VALUES (16,18);
INSERT INTO `tree_element_destination` (`el_id`,`el_id_destination`) VALUES (17,1);
INSERT INTO `tree_element_container` (`el_id`,`el_id_child`) VALUES (14,15);
INSERT INTO `tree_element_container` (`el_id`,`el_id_child`) VALUES (14,16);
INSERT INTO `tree_element_container` (`el_id`,`el_id_child`) VALUES (14,17);



INSERT INTO `tree`.`tree_element` (`el_id`, `tree_id`, `el_type_id`, `el_title`, `el_created_by`, `el_updated_by`) VALUES ('18', '1', '2', 'I am married to and living with a US Citizen.', '1', '1');
INSERT INTO `tree_element_order` (`el_id`,`el_order`) VALUES (18,4);
INSERT INTO `tree_element_container` (`el_id`,`el_id_child`) VALUES (1,18);
INSERT INTO `tree_element` (`el_id`,`tree_id`,`el_type_id`,`el_title`,`el_created_by`,`el_updated_by`) VALUES (19,1,3,'Yes',1,1);
INSERT INTO `tree_element` (`el_id`,`tree_id`,`el_type_id`,`el_title`,`el_created_by`,`el_updated_by`) VALUES (20,1,3,'No',1,1);
INSERT INTO `tree_element_order` (`el_id`,`el_order`) VALUES (19,1);
INSERT INTO `tree_element_order` (`el_id`,`el_order`) VALUES (20,0);
INSERT INTO `tree_element_destination` (`el_id`,`el_id_destination`) VALUES (20,6);
INSERT INTO `tree_element_destination` (`el_id`,`el_id_destination`) VALUES (19,21);
INSERT INTO `tree_element_container` (`el_id`,`el_id_child`) VALUES (18,19);
INSERT INTO `tree_element_container` (`el_id`,`el_id_child`) VALUES (18,20);

INSERT INTO `tree`.`tree_element` (`el_id`, `tree_id`, `el_type_id`, `el_title`, `el_created_by`, `el_updated_by`) VALUES ('21', '1', '2', 'I have been married to that US Citizen for at least the past three years.', '1', '1');
INSERT INTO `tree_element_order` (`el_id`,`el_order`) VALUES (21,5);
INSERT INTO `tree_element_container` (`el_id`,`el_id_child`) VALUES (1,21);
INSERT INTO `tree_element` (`el_id`,`tree_id`,`el_type_id`,`el_title`,`el_created_by`,`el_updated_by`) VALUES (22,1,3,'Yes',1,1);
INSERT INTO `tree_element` (`el_id`,`tree_id`,`el_type_id`,`el_title`,`el_created_by`,`el_updated_by`) VALUES (23,1,3,'No',1,1);
INSERT INTO `tree_element_order` (`el_id`,`el_order`) VALUES (22,1);
INSERT INTO `tree_element_order` (`el_id`,`el_order`) VALUES (23,0);
INSERT INTO `tree_element_destination` (`el_id`,`el_id_destination`) VALUES (23,6);
INSERT INTO `tree_element_destination` (`el_id`,`el_id_destination`) VALUES (22,23);
INSERT INTO `tree_element_container` (`el_id`,`el_id_child`) VALUES (21,22);
INSERT INTO `tree_element_container` (`el_id`,`el_id_child`) VALUES (21,21);


INSERT INTO `tree`.`tree_element` (`el_id`, `tree_id`, `el_type_id`, `el_title`, `el_created_by`, `el_updated_by`) VALUES ('26', '1', '2', 'During the past three years, I have not been out of the country for 18 months or more.', '1', '1');
INSERT INTO `tree_element_order` (`el_id`,`el_order`) VALUES (26,7);
INSERT INTO `tree_element_container` (`el_id`,`el_id_child`) VALUES (1,26);
INSERT INTO `tree_element` (`el_id`,`tree_id`,`el_type_id`,`el_title`,`el_created_by`,`el_updated_by`) VALUES (27,1,3,'Yes',1,1);
INSERT INTO `tree_element` (`el_id`,`tree_id`,`el_type_id`,`el_title`,`el_created_by`,`el_updated_by`) VALUES (28,1,3,'No',1,1);
INSERT INTO `tree_element_order` (`el_id`,`el_order`) VALUES (27,1);
INSERT INTO `tree_element_order` (`el_id`,`el_order`) VALUES (28,0);
INSERT INTO `tree_element_destination` (`el_id`,`el_id_destination`) VALUES (28,6);
INSERT INTO `tree_element_destination` (`el_id`,`el_id_destination`) VALUES (27,29);
INSERT INTO `tree_element_container` (`el_id`,`el_id_child`) VALUES (26,27);
INSERT INTO `tree_element_container` (`el_id`,`el_id_child`) VALUES (26,28);


INSERT INTO `tree`.`tree_element` (`el_id`, `tree_id`, `el_type_id`, `el_title`, `el_created_by`, `el_updated_by`) VALUES ('29', '1', '2', 'During the last five years, I have not been out of the US for 30 months or more.', '1', '1');
INSERT INTO `tree_element_order` (`el_id`,`el_order`) VALUES (29,8);
INSERT INTO `tree_element` (`el_id`,`tree_id`,`el_type_id`,`el_title`,`el_created_by`,`el_updated_by`) VALUES (30,1,3,'Yes',1,1);
INSERT INTO `tree_element` (`el_id`,`tree_id`,`el_type_id`,`el_title`,`el_created_by`,`el_updated_by`) VALUES (31,1,3,'No',1,1);
INSERT INTO `tree_element_order` (`el_id`,`el_order`) VALUES (30,1);
INSERT INTO `tree_element_order` (`el_id`,`el_order`) VALUES (31,0);
INSERT INTO `tree_element_destination` (`el_id`,`el_id_destination`) VALUES (31,6);
INSERT INTO `tree_element_destination` (`el_id`,`el_id_destination`) VALUES (30,5);
INSERT INTO `tree_element_container` (`el_id`,`el_id_child`) VALUES (29,30);
INSERT INTO `tree_element_container` (`el_id`,`el_id_child`) VALUES (29,31);


INSERT INTO `tree`.`tree_element` (`el_id`, `tree_id`, `el_type_id`, `el_title`, `el_created_by`, `el_updated_by`) VALUES ('33', '1', '2', 'My spouse has been a US Citizen for at least the past three years.', '1', '1');
INSERT INTO `tree_element_order` (`el_id`,`el_order`) VALUES (33,6);
INSERT INTO `tree_element_container` (`el_id`,`el_id_child`) VALUES (1,33);
INSERT INTO `tree_element` (`el_id`,`tree_id`,`el_type_id`,`el_title`,`el_created_by`,`el_updated_by`) VALUES (34,1,3,'Yes',1,1);
INSERT INTO `tree_element` (`el_id`,`tree_id`,`el_type_id`,`el_title`,`el_created_by`,`el_updated_by`) VALUES (35,1,3,'No',1,1);
INSERT INTO `tree_element_order` (`el_id`,`el_order`) VALUES (34,1);
INSERT INTO `tree_element_order` (`el_id`,`el_order`) VALUES (35,0);
INSERT INTO `tree_element_destination` (`el_id`,`el_id_destination`) VALUES (35,6);
INSERT INTO `tree_element_destination` (`el_id`,`el_id_destination`) VALUES (34,26);
INSERT INTO `tree_element_container` (`el_id`,`el_id_child`) VALUES (33,34);
INSERT INTO `tree_element_container` (`el_id`,`el_id_child`) VALUES (33,35);


INSERT INTO `tree`.`tree_element` (`el_id`, `tree_id`, `el_type_id`, `el_title`, `el_created_by`, `el_updated_by`) VALUES ('40', '1', '2', 'During the last three to five years, I have not taken a trip out of the United States that lasted one year or more.', '1', '1');
INSERT INTO `tree_element_order` (`el_id`,`el_order`) VALUES (40,7);
INSERT INTO `tree_element` (`el_id`,`tree_id`,`el_type_id`,`el_title`,`el_created_by`,`el_updated_by`) VALUES (41,1,3,'Yes',1,1);
INSERT INTO `tree_element` (`el_id`,`tree_id`,`el_type_id`,`el_title`,`el_created_by`,`el_updated_by`) VALUES (42,1,3,'No',1,1);
INSERT INTO `tree_element_order` (`el_id`,`el_order`) VALUES (41,1);
INSERT INTO `tree_element_order` (`el_id`,`el_order`) VALUES (42,0);
INSERT INTO `tree_element_destination` (`el_id`,`el_id_destination`) VALUES (42,6);
INSERT INTO `tree_element_destination` (`el_id`,`el_id_destination`) VALUES (41,43);
INSERT INTO `tree_element_container` (`el_id`,`el_id_child`) VALUES (40,41);
INSERT INTO `tree_element_container` (`el_id`,`el_id_child`) VALUES (40,42);


INSERT INTO `tree`.`tree_element` (`el_id`, `tree_id`, `el_type_id`, `el_title`, `el_created_by`, `el_updated_by`) VALUES ('43', '1', '2', 'I have resided in the district or state in which I am applying for citizenship for the last three months.', '1', '1');
INSERT INTO `tree_element_order` (`el_id`,`el_order`) VALUES (43,8);
INSERT INTO `tree_element` (`el_id`,`tree_id`,`el_type_id`,`el_title`,`el_created_by`,`el_updated_by`) VALUES (44,1,3,'Yes',1,1);
INSERT INTO `tree_element` (`el_id`,`tree_id`,`el_type_id`,`el_title`,`el_created_by`,`el_updated_by`) VALUES (45,1,3,'No',1,1);
INSERT INTO `tree_element_order` (`el_id`,`el_order`) VALUES (44,1);
INSERT INTO `tree_element_order` (`el_id`,`el_order`) VALUES (45,0);
INSERT INTO `tree_element_destination` (`el_id`,`el_id_destination`) VALUES (45,6);
INSERT INTO `tree_element_destination` (`el_id`,`el_id_destination`) VALUES (44,46);
INSERT INTO `tree_element_container` (`el_id`,`el_id_child`) VALUES (43,44);
INSERT INTO `tree_element_container` (`el_id`,`el_id_child`) VALUES (43,45);


INSERT INTO `tree`.`tree_element` (`el_id`, `tree_id`, `el_type_id`, `el_title`, `el_created_by`, `el_updated_by`) VALUES ('46', '1', '2', 'I can read, write and speak basic English.', '1', '1');
INSERT INTO `tree_element_order` (`el_id`,`el_order`) VALUES (46,8);
INSERT INTO `tree_element` (`el_id`,`tree_id`,`el_type_id`,`el_title`,`el_created_by`,`el_updated_by`) VALUES (47,1,3,'Yes',1,1);
INSERT INTO `tree_element` (`el_id`,`tree_id`,`el_type_id`,`el_title`,`el_created_by`,`el_updated_by`) VALUES (48,1,3,'No',1,1);
INSERT INTO `tree_element_order` (`el_id`,`el_order`) VALUES (47,1);
INSERT INTO `tree_element_order` (`el_id`,`el_order`) VALUES (48,0);
INSERT INTO `tree_element_destination` (`el_id`,`el_id_destination`) VALUES (48,6);
INSERT INTO `tree_element_destination` (`el_id`,`el_id_destination`) VALUES (47,49);
INSERT INTO `tree_element_container` (`el_id`,`el_id_child`) VALUES (46,47);
INSERT INTO `tree_element_container` (`el_id`,`el_id_child`) VALUES (46,48);


INSERT INTO `tree`.`tree_element` (`el_id`, `tree_id`, `el_type_id`, `el_title`, `el_created_by`, `el_updated_by`) VALUES ('52', '1', '2', 'I know the fundamentals of U.S. history and the form and principles of the U.S. government.', '1', '1');
INSERT INTO `tree_element_order` (`el_id`,`el_order`) VALUES (52,8);
INSERT INTO `tree_element` (`el_id`,`tree_id`,`el_type_id`,`el_title`,`el_created_by`,`el_updated_by`) VALUES (53,1,3,'Yes',1,1);
INSERT INTO `tree_element` (`el_id`,`tree_id`,`el_type_id`,`el_title`,`el_created_by`,`el_updated_by`) VALUES (54,1,3,'No',1,1);
INSERT INTO `tree_element_order` (`el_id`,`el_order`) VALUES (53,1);
INSERT INTO `tree_element_order` (`el_id`,`el_order`) VALUES (54,0);
INSERT INTO `tree_element_destination` (`el_id`,`el_id_destination`) VALUES (54,6);
INSERT INTO `tree_element_container` (`el_id`,`el_id_child`) VALUES (52,53);
INSERT INTO `tree_element_container` (`el_id`,`el_id_child`) VALUES (52,54);

/* Destination from Previous's Yes to This Question. */
INSERT INTO `tree_element_destination` (`el_id`,`el_id_destination`) VALUES (50,52);
