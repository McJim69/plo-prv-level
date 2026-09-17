drop database if exists plo_records_2010;
create database plo_records_2010;
use plo_records_2010;
	
	create table voters(
		vin			varchar(50) primary key,
		vname		varchar(100),
		precinct	varchar(50),
		address		text,
		sex			varchar(50),
		lit			varchar(50),
		_4p			varchar(50),
		ato			varchar(50),
		remarks		text,
		birth		date,
		province	varchar(50),
		city_mun	varchar(50),
		barangay	varchar(50),
		seq			varchar(50),
		ispicset  	int default 0
	);
	
	create table mce(
		vin			varchar(50) primary key,
		old_new		int,
		isoff		int,
		y_dec		int,
		nof			varchar(30),
		nom			varchar(30),
		nohw		varchar(30),
		pof_nohw	varchar(100),
		contact		varchar(20),
		foreign key(vin) references voters(vin) on delete cascade
	);
	
	create table bce(
		bno			int auto_increment primary key,
		mcevin		varchar(50),
		vin			varchar(50),
		old_new		int,
		isoff		int,
		y_dec		int,
		nof			varchar(30),
		nom			varchar(30),
		nohw		varchar(30),
		pof_nohw	varchar(100),
		contact		varchar(20),
		foreign key(vin) references voters(vin) on delete cascade,
		foreign key(mcevin) references mce(vin) on delete cascade
	);
	
	create table pl(
		plno		int auto_increment primary key,
		bcevin		varchar(50),
		vin			varchar(50),
		old_new		int,
		isoff		int,
		y_dec		int,
		nof			varchar(30),
		nom			varchar(30),
		nohw		varchar(30),
		pof_nohw	varchar(100),
		contact		varchar(20),
		foreign key(bcevin) references bce(vin) on delete cascade,
		foreign key(vin) references voters(vin) on delete cascade
	);
	
	create table hl(
		hlno		int auto_increment primary key,
		plvin		varchar(50),
		vin			varchar(50),
		old_new		int,
		isoff		int,
		y_dec		int,
		nof			varchar(30),
		nom			varchar(30),
		nohw		varchar(30),
		pof_nohw	varchar(100),
		contact		varchar(20),
		foreign key(plvin) references pl(vin) on delete cascade,
		foreign key(vin) references voters(vin) on delete cascade
	);

	create table hl_children(
		hlcno		int auto_increment primary key,
		hlvin		varchar(50),
		vin			varchar(50),
		contact		varchar(50),
		foreign key(hlvin) references hl(vin) on delete cascade,
		foreign key(vin) references voters(vin) on delete cascade
	);
	
	create table users(
		uno			int auto_increment primary key,
		fullname	varchar(100),
		position	varchar(100),
		username	varchar(100),
		password	varchar(100)
	);
		
	create table validity(
		validity	date
	);

	create table clusters(
		precinct	varchar(50) PRIMARY KEY,
		cluster		int
	);