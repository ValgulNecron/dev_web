drop database if exists exo1;

create database exo1;

use exo1;

create table personne
(
    NumPersonne  int not null auto_increment primary key,
    TypePersonne varchar(20),
    Ville        varchar(20)
);
create table PersonneMorale
(
    RaisonSociale varchar(20),
    SIRET         varchar(30),
    NumPersonne   int not null primary key references personne (NumPersonne)
);
create table PersonnePhysique
(
    NomDirigeant varchar(20),
    NumPersonne  int not null primary key references personne (NumPersonne)
);

-- engine=inoDb permet de creer une table avec le moteur innoDB qui est a partir de la verion 5.5.5
-- de mysql le moteur par defaut pour les tables
-- charset=utf8 permet de definir l'encodage des caracteres de la table en utf8 (utf8_general_ci)


DROP TRIGGER IF EXISTS create_default_personne;
DELIMITER $$
CREATE TRIGGER create_default_personne
    AFTER INSERT
    ON Personne
    FOR EACH ROW
BEGIN
    -- insertion d’une personne morale
    IF NEW.TypePersonne = 'M' THEN
        INSERT INTO PersonneMorale(NumPersonne) VALUES (NEW.NumPersonne) ;
-- sinon insertion d’une personne physique
    ELSE
        if NEW.TypePersonne = 'P' THEN
            INSERT INTO PersonnePhysique(NumPersonne) VALUES (NEW.NumPersonne) ;
        END IF;
    end if;
END $$
DELIMITER ;

insert into personne (TypePersonne, Ville)
values ('M', 'Paris');
insert into personne (TypePersonne, Ville)
values ('P', 'Paris');

update personnephysique
set NomDirigeant = 'Dupont'
where NumPersonne = 2;
update PersonneMorale
set RaisonSociale = 'Société X'
where NumPersonne = 1;

drop trigger if exists create_default_personne;

drop trigger if exists verifSuppression;
delimiter $$
create trigger verifSuppression
    before delete
    on personne
    for each row
begin
    signal sqlstate '45000' set message_text = ' INTERDIT DE SUPPRIMER UN ENREGISTREMENT DE LA TABLE PERSONNE';
end $$
delimiter ;

drop trigger if exists verifNewPersonne;
delimiter $$
create trigger verifNewPersonne
    before insert
    on personne
    for each row
begin
    if new.TypePersonne not in ('M', 'P') then
        signal sqlstate '45000' set message_text = 'Le type de personne doit être M ou P';
    end if;
end $$
drop trigger if exists verifNExistePasPhysique;
delimiter $$
create trigger verifNExistePasPhysique
    before insert
    on PersonnePhysique
    for each row
begin
    set @n = (select TypePersonne from Personne where NumPersonne = NEW.NumPersonne);
    if @n != 'P' then
        signal sqlstate '45000' set message_text = 'Le type associé au numéro de personne doit être P';
    end if;
    if exists(select * from PersonneMorale where NumPersonne = NEW.NumPersonne) then
        signal sqlstate '45000' set message_text = 'Le numéro de personne existe déjà dans la table PersonneMorale';
    end if;
end $$
drop trigger if exists verifNExistePasMorale;
delimiter $$
create trigger verifNExistePasMorale
    before insert
    on PersonneMorale
    for each row
begin
    set @n = (select TypePersonne from Personne where NumPersonne = NEW.NumPersonne);
    if @n != 'M' then
        signal sqlstate '45000' set message_text = 'Le type associé au numéro de personne doit être M';
    end if;
    if exists(select * from PersonnePhysique where NumPersonne = NEW.NumPersonne) then
        signal sqlstate '45000' set message_text = 'Le numéro de personne existe déjà dans la table PersonnePhysique';
    end if;
end $$


insert into personne (TypePersonne, Ville)
values ('P', 'Paris');
insert into personne (TypePersonne, Ville)
values ('M', 'Paris');
insert into PersonnePhysique (NumPersonne, NomDirigeant)
values (3, 'Dupont');
insert into PersonneMorale (NumPersonne, RaisonSociale)
values (4, 'Société X');
insert into PersonnePhysique (NumPersonne, NomDirigeant)
values (4, 'Dupont');


delete
from personne
where NumPersonne = 1;


drop procedure if exists insertEtUpdatePersonne;
delimiter $$
create procedure insertEtUpdatePersonne(NumP int, TypeP char(1), VilleP varchar(20), RaisonSocialeP varchar(20),
                                        SIRETP varchar(30))
begin
    insert into personne (NumPersonne, TypePersonne, Ville)
    values (NumP, TypeP, VilleP);
    if TypeP = 'M' then
        insert into PersonneMorale (NumPersonne, RaisonSociale, SIRET)
        values (NumP, RaisonSocialeP, SIRETP);
    else
        insert into PersonnePhysique (NumPersonne, NomDirigeant)
        values (NumP, RaisonSocialeP);
    end if;
end $$
delimiter ;

call insertEtUpdatePersonne(5, 'M', 'Strasbourg', 'Dupont', '8942794184917491');

drop function if exists comptePersonne;
delimiter $$
create function comptePersonne(v varchar(20))
    returns int
    deterministic
begin
    return (select count(*) from personne where Ville = v);
end $$
delimiter ;

select comptePersonne('Strasbourg');


