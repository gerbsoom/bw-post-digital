CREATE TABLE invheader (                                                     
  invid int(11) NOT NULL AUTO_INCREMENT,                                             
  invdate date NOT NULL,                                                          
  client_id int(11) NOT NULL,                                                     
  amount decimal(10,2) NOT NULL DEFAULT '0.00',                                   
  tax decimal(10,2) NOT NULL DEFAULT '0.00',                                      
  total decimal(10,2) NOT NULL DEFAULT '0.00',                                    
  note char(100) DEFAULT NULL,                                 
  PRIMARY KEY  (invid) 
);