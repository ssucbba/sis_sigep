/***********************************I-SCP-JRR-SIGEP-1-01/11/2018****************************************/
/*CREATE TABLE sigep.ttype_sigep_service_request (
    id_type_sigep_service_request serial NOT NULL,
    id_type_service_request INTEGER NOT NULL,
    sigep_service_name VARCHAR(200),
    sigep_url VARCHAR(200) NOT NULL,
    method_type VARCHAR(8) NOT NULL,
    time_to_refresh INTEGER,
    exec_order INTEGER NOT NULL,
    queue_url VARCHAR(200),
    queue_method VARCHAR(8),
    revert_url VARCHAR(200),
    revert_method VARCHAR(8),   
    user_param VARCHAR(100),
    json_main_container VARCHAR(200) ,
    sigep_main_container VARCHAR(200) 
) INHERITS (pxp.tbase);

ALTER TABLE ONLY sigep.ttype_sigep_service_request
    ADD CONSTRAINT pk_type_sigep_service_request
    PRIMARY KEY (id_type_sigep_service_request);
    
CREATE TABLE sigep.ttype_service_request (
    id_type_service_request serial NOT NULL,    
    service_code VARCHAR(50) NOT NULL,
    description TEXT NOT NULL
) INHERITS (pxp.tbase);

ALTER TABLE ONLY sigep.ttype_service_request
    ADD CONSTRAINT pk_type_service_request
    PRIMARY KEY (id_type_service_request);
    
CREATE TABLE sigep.tparam (
	id_param SERIAL NOT NULL,
    id_type_sigep_service_request INTEGER NOT NULL,    
    sigep_name VARCHAR(200),
    erp_json_container VARCHAR(200),
    erp_name VARCHAR(200),
    ctype VARCHAR(50) NOT NULL,
    input_output VARCHAR(10) NOT NULL,
    def_value VARCHAR(500), 
) INHERITS (pxp.tbase);

ALTER TABLE ONLY sigep.tparam
    ADD CONSTRAINT pk_tparam
    PRIMARY KEY (id_param);
    

CREATE TABLE sigep.tservice_request (
	id_service_request SERIAL NOT NULL,
    id_type_service_request INTEGER NOT NULL,    
    sys_origin VARCHAR(100) NOT NULL,
    ip_origin VARCHAR(50) NOT NULL,
    status VARCHAR(100) NOT NULL,
    date_finished TIMESTAMP,
    last_message TEXT, 
    last_message_revert TEXT
) INHERITS (pxp.tbase);

ALTER TABLE ONLY sigep.tservice_request
    ADD CONSTRAINT pk_tservice_request
    PRIMARY KEY (id_service_request);
    
CREATE TABLE sigep.tsigep_service_request (
	id_sigep_service_request SERIAL NOT NULL,
    id_service_request INTEGER NOT NULL,
    id_type_sigep_service_request INTEGER NOT NULL,    
    date_request_sent TIMESTAMP,
    date_queue_sent TIMESTAMP,
    status VARCHAR(100) NOT NULL,    
    last_message TEXT,
    last_message_revert TEXT,
    exec_order INTEGER NOT NULL,
    user_name VARCHAR(100),
    queue_id VARCHAR(100),
    queue_revert_id VARCHAR(100)
     
) INHERITS (pxp.tbase);

ALTER TABLE ONLY sigep.tsigep_service_request
    ADD CONSTRAINT pk_tsigep_service_request
    PRIMARY KEY (id_sigep_service_request);
    
CREATE TABLE sigep.trequest_param (
	id_request_param SERIAL NOT NULL,
    id_sigep_service_request INTEGER NOT NULL,    
    name VARCHAR(200) NOT NULL,
    value TEXT NOT NULL,
    ctype VARCHAR(50) NOT NULL,
    input_output VARCHAR(10) NOT NULL
) INHERITS (pxp.tbase);

ALTER TABLE ONLY sigep.trequest_param
    ADD CONSTRAINT pk_trequest_param
    PRIMARY KEY (id_request_param);*/

/***********************************F-SCP-JRR-SIGEP-1-01/11/2018****************************************/
/***********************************I-SCP-JRR-SIGEP-2-27/06/2019****************************************/
CREATE TABLE sigep.ttype_sigep_service_request (
  id_type_sigep_service_request SERIAL,
  id_type_service_request INTEGER NOT NULL,
  sigep_service_name VARCHAR(200),
  sigep_url VARCHAR(200) NOT NULL,
  method_type VARCHAR(8) NOT NULL,
  time_to_refresh INTEGER,
  exec_order INTEGER NOT NULL,
  queue_url VARCHAR(200),
  queue_method VARCHAR(8),
  revert_url VARCHAR(200),
  revert_method VARCHAR(8),
  user_param VARCHAR(100),
  json_main_container VARCHAR(200),
  sigep_main_container VARCHAR(200),
  CONSTRAINT pk_type_sigep_service_request PRIMARY KEY(id_type_sigep_service_request),
  CONSTRAINT fk_type_sigep_service_request__id_type_service_request FOREIGN KEY (id_type_service_request)
    REFERENCES sigep.ttype_service_request(id_type_service_request)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
    NOT DEFERRABLE
) INHERITS (pxp.tbase)
WITH (oids = false);

ALTER TABLE sigep.ttype_sigep_service_request
  OWNER TO postgres;


CREATE TABLE sigep.ttype_service_request (
  id_type_service_request SERIAL,
  service_code VARCHAR(50) NOT NULL,
  description TEXT NOT NULL,
  CONSTRAINT pk_type_service_request PRIMARY KEY(id_type_service_request)
) INHERITS (pxp.tbase)
WITH (oids = false);

ALTER TABLE sigep.ttype_service_request
  OWNER TO postgres;


CREATE TABLE sigep.tparam (
  id_param SERIAL,
  id_type_sigep_service_request INTEGER NOT NULL,
  sigep_name VARCHAR(200),
  erp_json_container VARCHAR(200),
  erp_name VARCHAR(200),
  ctype VARCHAR(50) NOT NULL,
  input_output VARCHAR(10) NOT NULL,
  def_value VARCHAR(500),
  CONSTRAINT pk_tparam PRIMARY KEY(id_param),
  CONSTRAINT fk_tparam__id_type_sigep_service_request FOREIGN KEY (id_type_sigep_service_request)
    REFERENCES sigep.ttype_sigep_service_request(id_type_sigep_service_request)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
    NOT DEFERRABLE
) INHERITS (pxp.tbase)
WITH (oids = false);

ALTER TABLE sigep.tparam
  OWNER TO postgres;


CREATE TABLE sigep.tservice_request (
  id_service_request SERIAL,
  id_type_service_request INTEGER NOT NULL,
  sys_origin VARCHAR(100) NOT NULL,
  ip_origin VARCHAR(50) NOT NULL,
  status VARCHAR(100) NOT NULL,
  date_finished TIMESTAMP WITHOUT TIME ZONE,
  last_message TEXT,
  last_message_revert TEXT,
  CONSTRAINT pk_tservice_request PRIMARY KEY(id_service_request),
  CONSTRAINT fk_tservice_request__id_type_service_request FOREIGN KEY (id_type_service_request)
    REFERENCES sigep.ttype_service_request(id_type_service_request)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
    NOT DEFERRABLE
) INHERITS (pxp.tbase)
WITH (oids = false);

ALTER TABLE sigep.tservice_request
  OWNER TO postgres;


CREATE TABLE sigep.tsigep_service_request (
  id_sigep_service_request SERIAL,
  id_service_request INTEGER NOT NULL,
  id_type_sigep_service_request INTEGER NOT NULL,
  date_request_sent TIMESTAMP WITHOUT TIME ZONE,
  date_queue_sent TIMESTAMP WITHOUT TIME ZONE,
  status VARCHAR(100) NOT NULL,
  last_message TEXT,
  exec_order INTEGER NOT NULL,
  user_name VARCHAR(100),
  queue_id VARCHAR(100),
  queue_revert_id VARCHAR(100),
  last_message_revert TEXT,
  CONSTRAINT pk_tsigep_service_request PRIMARY KEY(id_sigep_service_request),
  CONSTRAINT fk_sigep_service_request__id_service_request FOREIGN KEY (id_service_request)
    REFERENCES sigep.tservice_request(id_service_request)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
    NOT DEFERRABLE,
  CONSTRAINT fk_tsigep_service_request__id_type_sigep_service_request FOREIGN KEY (id_type_sigep_service_request)
    REFERENCES sigep.ttype_sigep_service_request(id_type_sigep_service_request)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
    NOT DEFERRABLE
) INHERITS (pxp.tbase)
WITH (oids = false);

ALTER TABLE sigep.tsigep_service_request
  OWNER TO postgres;


CREATE TABLE sigep.trequest_param (
  id_request_param SERIAL,
  id_sigep_service_request INTEGER NOT NULL,
  name VARCHAR(200) NOT NULL,
  value TEXT NOT NULL,
  ctype VARCHAR(50) NOT NULL,
  input_output VARCHAR(10) NOT NULL,
  CONSTRAINT pk_trequest_param PRIMARY KEY(id_request_param),
  CONSTRAINT fk_trequest_param__id_sigep_service_request FOREIGN KEY (id_sigep_service_request)
    REFERENCES sigep.tsigep_service_request(id_sigep_service_request)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
    NOT DEFERRABLE
) INHERITS (pxp.tbase)
WITH (oids = false);

ALTER TABLE sigep.trequest_param
  OWNER TO postgres;


CREATE TABLE sigep.tuser_mapping (
  id_user_mapping SERIAL,
  pxp_user VARCHAR(100),
  sigep_user VARCHAR(100),
  refresh_token VARCHAR(200),
  access_token VARCHAR(200),
  expires_in INTEGER,
  date_issued_rt TIMESTAMP WITHOUT TIME ZONE,
  date_issued_at TIMESTAMP WITHOUT TIME ZONE,
  CONSTRAINT tuser_mapping_pkey PRIMARY KEY(id_user_mapping)
) INHERITS (pxp.tbase)
WITH (oids = false);

ALTER TABLE sigep.tuser_mapping
  ALTER COLUMN id_user_mapping SET STATISTICS 0;

ALTER TABLE sigep.tuser_mapping
  OWNER TO postgres;
/***********************************F-SCP-JRR-SIGEP-2-27/06/2019****************************************/