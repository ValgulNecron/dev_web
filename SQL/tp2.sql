drop database if exists parcImmobilier;
create database parcImmobilier;
use parcImmobilier;

create table immeuble
(
    num             int          not null auto_increment primary key,
    numRue          varchar(20)  not null,
    nomRue          varchar(100) not null,
    codePostal      varchar(10)  not null,
    ville           varchar(100) not null,
    fiberOptique    boolean      not null,
    parkingPrivatif boolean      not null
);

create table appartement
(
    num              int         not null auto_increment,
    numImmeuble      int         not null,
    loyerMensuel     double      not null,
    superficieTotale double,
    terrasse         bool        not null,
    classeConso      varchar(20) not null,
    placeDeParking   bool        not null,
    prixParking      double,
    primary key (num, numImmeuble),
    foreign key (numImmeuble) references immeuble (num)
);

create table photo
(
    reference      varchar(20)   not null,
    numAppartement int           not null,
    titre          varchar(100)  not null,
    description    varchar(1000) not null,
    uri            varchar(1000) not null,
    primary key (reference, numAppartement),
    foreign key (numAppartement) references appartement (num)
);

create table piece
(
    num            int          not null auto_increment,
    numAppartement int          not null,
    fonction       varchar(100) not null,
    superficie     double       not null,
    primary key (num, numAppartement),
    foreign key (numAppartement) references appartement (num)
);

drop trigger if exists verifAppartementParking;
delimiter $$
create trigger verifAppartementParking
    before insert
    on appartement
    for each row
begin
    declare parking bool;
    if new.placeDeParking = true and new.prixParking is null then
        signal sqlstate '45000' set message_text = 'Le prix du parking doit être renseigné';
    end if;
    if new.placeDeParking = true and new.prixParking < 0 then
        signal sqlstate '45000' set message_text = 'Le prix du parking doit être positif';
    end if;
    if new.placeDeParking = false and new.prixParking is not null then
        signal sqlstate '45000' set message_text = 'Le prix du parking doit être null';
    end if;
    -- ajout question 3
    select parkingPrivatif into parking from immeuble where num = new.numImmeuble;
    if new.placeDeParking = true and parking = false then
        signal sqlstate '45000' set message_text = 'L''immeuble ne possède pas de parking';
    end if;
end$$
delimiter ;

drop trigger if exists superficieAppartement;
delimiter $$
create trigger superficieAppartement
    after insert
    on piece
    for each row
begin
    update appartement
    set superficieTotale = superficieTotale + new.superficie
    where num = new.numAppartement;
end$$
delimiter ;

drop trigger if exists initialiserSuperficie;
delimiter $$
create trigger initialiserSuperficie
    before insert
    on appartement
    for each row
begin
    set new.superficieTotale = 0;
end$$
delimiter ;

drop trigger if exists verifUpdatePiece;
delimiter $$
create trigger verifUpdatePiece
    before update
    on piece
    for each row
begin
    update appartement
    set superficieTotale = superficieTotale - old.superficie + new.superficie
    where num = new.numAppartement;
end$$
delimiter ;

insert into immeuble(numRue, nomRue, codePostal, ville, fiberOptique, parkingPrivatif)
values ('1', 'rue de la paix', '75000', 'Paris', true, true),
       ('2', 'rue de la paix', '75000', 'Paris', true, false),
       ('3', 'rue de la paix', '75000', 'Paris', false, false),
       ('4', 'rue de la paix', '75000', 'Paris', false, true);
insert into appartement(numImmeuble, loyerMensuel, terrasse, classeConso, placeDeParking, prixParking)
values (1, 1000, true, 'A', true, 100),
       (1, 1000, true, 'A', false, null),
       (1, 1000, true, 'A', true, 100),
       (2, 1000, true, 'A', false, null),
       (2, 1000, true, 'A', false, null),
       (2, 1000, true, 'A', false, null),
       (3, 1000, true, 'A', false, null),
       (3, 1000, true, 'A', false, null),
       (3, 1000, true, 'A', false, null),
       (4, 1000, true, 'A', true, 100),
       (4, 1000, true, 'A', false, null),
       (4, 1000, true, 'A', true, 100);
insert into piece(numAppartement, fonction, superficie)
values (1, 'cuisine', 10),
       (1, 'salon', 20),
       (1, 'chambre', 30),
       (2, 'cuisine', 10),
       (2, 'salon', 20),
       (2, 'chambre', 30),
       (3, 'cuisine', 10),
       (3, 'salon', 20),
       (3, 'chambre', 30);
insert into photo(reference, numAppartement, titre, description, uri)
values ('1', 1, 'photo1', 'photo1', 'photo1'),
       ('2', 1, 'photo2', 'photo2', 'photo2'),
       ('3', 1, 'photo3', 'photo3', 'photo3'),
       ('4', 1, 'photo4', 'photo4', 'photo4'),
       ('5', 1, 'photo5', 'photo5', 'photo5'),
       ('6', 1, 'photo6', 'photo6', 'photo6'),
       ('7', 1, 'photo7', 'photo7', 'photo7'),
       ('8', 1, 'photo8', 'photo8', 'photo8'),
       ('9', 1, 'photo9', 'photo9', 'photo9');

insert into immeuble(numRue, nomRue, codePostal, ville, fiberOptique, parkingPrivatif)
values ('1', 'rue de la paix', '75000', 'Paris', true, false);
insert into appartement(numImmeuble, loyerMensuel, superficieTotale, terrasse, classeConso, placeDeParking, prixParking)
values (1, 1000, 10, true, 'A', true, 100);
insert into piece(numAppartement, fonction, superficie)
values (1, 'cuisine', 10);
update piece
set superficie = 20
where numAppartement = 1
  and fonction = 'cuisine';

drop trigger if exists verifUpdateSuperficie;
delimiter $$
create trigger verifUpdateSuperficie
    before update
    on appartement
    for each row
begin
    if new.superficieTotale is not null then
        signal sqlstate '45000' set message_text = 'La superficie totale ne peut pas être modifiée';
    end if;
end$$
delimiter ;

insert into appartement(numImmeuble, loyerMensuel, terrasse, classeConso, placeDeParking, prixParking)
values (2, 1000, true, 'A', true, 100),
       (2, 1000, true, 'A', false, 100);
update piece
set superficie = 20
where numAppartement = 1
  and fonction = 'cuisine';