-- Initial

create database camera;
use camera;

grant all privileges on camera.* to camera@localhost identified by 'xxxxxxxx';
grant select on camera.* to camselect@localhost;

CREATE TABLE position (
id BIGINT PRIMARY KEY,
latitude INT,
longitude INT);

CREATE TABLE tag (
id BIGINT REFERENCES position ON DELETE CASCADE,
k VARCHAR(100),
v VARCHAR(10000),
PRIMARY KEY (id, k) 
);

CREATE INDEX LatLon ON position (latitude, longitude);
 
