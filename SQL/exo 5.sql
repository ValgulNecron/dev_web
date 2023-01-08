drop database if exists exo5;
create database exo5;
use exo5;

create table employe(
    ENO int primary key,
    ENOM varchar(150),
    PROF varchar(150),
    DATEEMB date,
    SAL int,
    COMM int,
    DNO int,
    DNOM varchar(150),
    DIR int,
    VILLE varchar(1000)
);

create table competenceEmploye(
    NCOMP int,
    TCOMP varchar(150),
    ENO int,
    primary key (NCOMP, ENO)
);

create table machine(
    MNU int,
    MNO varchar(150),
    NCOMP int,
    foreign key (NCOMP) references competenceEmploye(NCOMP),
    primary key (MNU,NCOMP)
);

create table reparation(
    MNU int,
    ENO int,
    dateRep date,
    foreign key (MNU) references machine(MNU),
    foreign key (ENO) references employe(ENO),
    primary key (MNU, ENO, dateRep)
);

insert into employe values(1, 'LEDRU', 'TECH1', '2000-01-10', 2500, 0, 1, 'INFO', 1, 'Strasbourg'),
                          (2, 'MANIR', 'TECH2', '2001-01-12', 1500, 0, 1, 'INFO', 1, 'Strasbourg'),
                          (3, 'CIROU', 'DRH', '2000-01-10', 2500, 1000, 2, 'RH', 3, 'Strasbourg'),
                          (4, 'LEDRU', 'Ingenieur', '2000-04-24', 1500, 0, 'INFO', 1 ,'Strasbourg');

insert into competenceEmploye values(1, 'Revision', 1),(2, 'Reglage', 2),(3, 'Reparation', 2),(1, 'Revision', 2);

insert into machine values(1,'M1',1),(2,'M2',1),(1,'M1',2),(2,'M2',2),(1,'M1',3),(2,'M2',3);


drop function if exists peutReparer;
delimiter $$
create function peutReparer(in E int, in M int) RETURNS bool deterministic
begin
    declare TMNU int;
    declare vide int default 0;
    declare curMachine cursor for select NCOMP from machine where MNU = M;
    declare continue handler for not found set vide = 1;
    open curMachine;
    loopC:
    loop
        fetch curMachine into TMNU;
        if TMNU not in (select NCOMP from competenceEmploye where ENO = E) then
            return 0;
        end if;
        if vide = 1 then
            leave loopC;
        end if;
    end loop loopC;
    close curMachine;
    return 1;
end$$
delimiter ;

drop trigger if exists peutReparerTrigger;
delimiter $$
create trigger peutReparerTrigger before insert on reparation
for each row
begin
    if peutReparer(new.ENO, new.MNU) = 0 then
        signal sqlstate '45000' set message_text = 'L''employe ne peut pas reparer cette machine';
    end if;
end$$

select DNOM from employe where DNO in (select DNO from employe where PROF = 'Ingenieur');
select SAL, DNOM from employe where SAL > (select min(SAL) from employe where PROF = 'Ingenieur');
select SAL, DNOM from employe where SAL > (select max(SAL) from employe where PROF = 'Ingenieur');
select ENOM, (select ENOM from employe where ENO = DIR) as DIR from employe;
select ENOM from employe where DIR = (select DIR from employe where ENOM = 'JIM');
select ENOM, DATEEMB from employe where DATEEMB < (select DATEEMB from employe where ENO = DIR);
