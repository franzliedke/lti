CREATE TABLE lti_consumer (
  consumer_key varchar(255) NOT NULL,
  name varchar(45) NOT NULL,
  secret varchar(32) NOT NULL,
  lti_version varchar(12) DEFAULT NULL,
  consumer_name varchar(255) DEFAULT NULL,
  consumer_version varchar(255) DEFAULT NULL,
  consumer_guid varchar(255) DEFAULT NULL,
  css_path varchar(255) DEFAULT NULL,
  protected tinyint NOT NULL,
  enabled tinyint NOT NULL,
  enable_from datetime DEFAULT NULL,
  enable_until datetime DEFAULT NULL,
  last_access date DEFAULT NULL,
  created datetime NOT NULL,
  updated datetime NOT NULL,
  PRIMARY KEY (consumer_key)
);

CREATE TABLE lti_context (
  consumer_key varchar(255) NOT NULL,
  context_id varchar(255) NOT NULL,
  lti_context_id varchar(255) DEFAULT NULL,
  lti_resource_id varchar(255) DEFAULT NULL,
  title varchar(255) NOT NULL,
  settings text,
  primary_consumer_key varchar(255) DEFAULT NULL,
  primary_context_id varchar(255) DEFAULT NULL,
  share_approved tinyint DEFAULT NULL,
  created datetime NOT NULL,
  updated datetime NOT NULL,
  PRIMARY KEY (consumer_key, context_id)
);

CREATE TABLE lti_user (
  consumer_key varchar(255) NOT NULL,
  context_id varchar(255) NOT NULL,
  user_id varchar(255) NOT NULL,
  lti_result_sourcedid varchar(255) NOT NULL,
  created datetime NOT NULL,
  updated datetime NOT NULL,
  PRIMARY KEY (consumer_key, context_id, user_id)
);

CREATE TABLE lti_nonce (
  consumer_key varchar(255) NOT NULL,
  value varchar(32) NOT NULL,
  expires datetime NOT NULL,
  PRIMARY KEY (consumer_key, value)
);

CREATE TABLE lti_share_key (
  share_key_id varchar(32) NOT NULL,
  primary_consumer_key varchar(255) NOT NULL,
  primary_context_id varchar(255) NOT NULL,
  auto_approve tinyint NOT NULL,
  expires datetime NOT NULL,
  PRIMARY KEY (share_key_id)
);

ALTER TABLE lti_context
  ADD CONSTRAINT lti_context_consumer_FK1 FOREIGN KEY (consumer_key)
	 REFERENCES lti_consumer (consumer_key);

ALTER TABLE lti_context
  ADD CONSTRAINT lti_context_context_FK1 FOREIGN KEY (primary_consumer_key, primary_context_id)
	 REFERENCES lti_context (consumer_key, context_id);

ALTER TABLE lti_user
  ADD CONSTRAINT lti_user_context_FK1 FOREIGN KEY (consumer_key, context_id)
	 REFERENCES lti_context (consumer_key, context_id);

ALTER TABLE lti_nonce
	ADD CONSTRAINT lti_nonce_consumer_FK1 FOREIGN KEY (consumer_key)
	 REFERENCES lti_consumer (consumer_key);

ALTER TABLE lti_share_key
  ADD CONSTRAINT lti_share_key_context_FK1 FOREIGN KEY (primary_consumer_key, primary_context_id)
	 REFERENCES lti_context (consumer_key, context_id);
