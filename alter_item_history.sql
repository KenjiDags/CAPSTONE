USE tesda_inventory;

ALTER TABLE item_history ADD COLUMN ics_id INT(11) DEFAULT NULL AFTER ris_id;
ALTER TABLE item_history ADD CONSTRAINT fk_item_history_ics FOREIGN KEY (ics_id) REFERENCES ics(ics_id);
