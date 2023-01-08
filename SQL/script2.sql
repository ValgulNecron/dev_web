Drop database if EXISTS entrepriseLegrand;
create database if not EXISTS entrepriseLegrand;

use entrepriseLegrand;

create table CLIENT
(
    NumClient       varchar(20) not null,
    NomClient       varchar(20),
    VilleClient     varchar(40),
    ContactClient   varchar(80),
    CategorieClient varchar(20)
);
create table EMPLOYE
(
    NumEmploye    varchar(20) not null,
    NomEmploye    varchar(20),
    PrenomEmploye varchar(20),
    RoleEmploye   varchar(20),
    LogEmploye    varchar(20),
    MdpEmploye    varchar(20)
);
Create table APPEL
(
    NumAppel         varchar(20) not null,
    DateAppel        date,
    DescriptionAppel varchar(80),
    EtatAppel        varchar(20),
    TypeAppel        varchar(20),
    NumClient        varchar(20),
    NumEmploye       varchar(20)
);
create table CAHIER
(
    NumCahier     varchar(20) not null,
    DateCahier    date,
    MontantCahier float,
    NumClient     varchar(20),
    NumEmploye    varchar(20),
    NumAppel      varchar(20)
);
create table LIGNE_CAHIER
(
    NumLigneCahier           int not null,
    NumCahier                varchar(20),
    DescriptionArticleCahier varchar(80)
);

alter table CLIENT
    add primary key (NumClient);
alter table EMPLOYE
    add primary key (NumEmploye);
alter table APPEL
    add primary key (NumAppel);
alter table CAHIER
    add primary key (NumCahier);
alter table LIGNE_CAHIER
    add primary key (NumLigneCahier, NumCahier);

alter table APPEL
    add foreign key (NumClient) references CLIENT (NumClient);
alter table APPEL
    add foreign key (NumEmploye) references EMPLOYE (NumEmploye);
alter table CAHIER
    add foreign key (NumClient) references CLIENT (NumClient);
alter table CAHIER
    add foreign key (NumEmploye) references EMPLOYE (NumEmploye);
alter table CAHIER
    add foreign key (NumAppel) references APPEL (NumAppel);
alter table LIGNE_CAHIER
    add foreign key (NumCahier) references CAHIER (NumCahier);

insert into CLIENT
values (1, 'Legrand', 'Paris', 'contact du client 1', 'Categorie 1')
     , (2, 'entrepriseB23', 'Strasbourg', 'entrepriseB23@B23.NET', 'categorieB23')
     , (3, 'entrepriseC23', 'Lyon', 'entrepriseC23@C23.net', 'categorieC23');
insert into EMPLOYE
values (1, 'Legrand', 'Jean', 'Commercial', 'legrand', 'legrand'),
       (2, 'B23', 'B23', 'Dev', 'B23', 'B23'),
       (3, 'C23', 'C23', 'Admin reseaux', 'C23', 'C23'),
       (4, 'paul', 'grimaud', 'dev', 'B', 'B');
insert into APPEL
values (1, '2018-01-01', 'Description de l appel 1', 'Etat de l appel 1', 'Type de l appel 1', 1, 1)
     , (2, '2018-01-02', 'Description de l appel 2', 'Etat de l appel 2', 'Type de l appel 2', 2, 2)
     , (3, '2018-01-03', 'Description de l appel 3', 'Etat de l appel 3', 'Type de l appel 3', 3, 3);
insert into CAHIER
values (1, '2018-01-01', 100, 1, 1, 1)
     , (2, '2018-01-02', 200, 2, 2, 2)
     , (3, '2018-01-03', 300, 3, 3, 3)
     , (4, '2011-01-04', 400, 1, 1, 1)
     , (5, '2011-01-05', 500, 2, 2, 2)
     , (6, '2011-01-06', 600, 3, 3, 3)
     , (7, '2018-01-01', 100, 1, 4, 1)
     , (8, '2018-01-02', 200, 2, 4, 2)
     , (9, '2018-01-03', 300, 3, 4, 3);

insert into LIGNE_CAHIER
values (1, 1, 'Description de l article 1')
     , (2, 2, 'Description de l article 2')
     , (3, 3, 'Description de l article 3')
     , (4, 4, 'Description de l article 4')
     , (5, 5, 'Description de l article 5')
     , (6, 6, 'Description de l article 6')
     , (7, 7, 'Description de l article 7')
     , (8, 8, 'Description de l article 8')
     , (9, 9, 'Description de l article 9')
     , (10, 7, 'Description de l article 10')
     , (11, 8, 'Description de l article 11')
     , (12, 9, 'Description de l article 12')
     , (13, 7, 'Description de l article 13')
     , (14, 8, 'Description de l article 14')
     , (15, 9, 'Description de l article 15');


insert into EMPLOYE
values ('DS453', 'DUPONT', 'Sylvain', 'dev', 'dupont', 'dupont');
insert into CLIENT
values ('CF356', 'Coca', 'Paris', 'contact du client 1', 'Categorie 1');

select CAHIER.Numcahier, CAHIER.DateCahier, count(LIGNE_CAHIER.NumLigneCahier) as count
from LIGNE_CAHIER,
     CAHIER
where CAHIER.NumCahier = LIGNE_CAHIER.NumCahier
group by CAHIER.NumCahier, CAHIER.DateCahier;
select sum(MontantCahier)
from CAHIER,
     EMPLOYE
where CAHIER.NumEmploye = EMPLOYE.NumEmploye
  and EMPLOYE.NomEmploye = 'grimaud'
  and EMPLOYE.PrenomEmploye = 'paul';


insert into CAHIER(NumCahier, DateCahier, MontantCahier, NumClient, NumEmploye)
values ('D202001345', '2020-03-24', 35000, 'CF356', 'DS453');
insert into LIGNE_CAHIER
values (1, 'D202001345', 'extension du parking visiteur 1')
     , (2, 'D202001345', 'realisation d un nouveaux portail automatique');

update CAHIER
set DateCahier = '2020-03-25'
where DateCahier = '2020-03-24';

alter table APPEL
    add CommentaireAppel varchar(100) default '';


insert into APPEL(NumAppel, DateAppel, DescriptionAppel, EtatAppel, TypeAppel, NumClient, NumEmploye)
values ('A202001345', '2020-03-24', 'demande de devis pour la construction d un nouveau batiment', 'en cours', 'devis',
        'CF356', 'DS453');

delete
from LIGNE_CAHIER
where NumCahier =
      (select NumCahier from CAHIER where NumAppel = (select NumAppel from APPEL where APPEL.CommentaireAppel = ''));
delete
from CAHIER
where NumAppel = (select NumAppel from APPEL where APPEL.CommentaireAppel = '');
delete
from APPEL
where CommentaireAppel = '';

alter table APPEL
    drop CommentaireAppel;



CREATE TABLE agence
(
    codeT   VARCHAR(50) PRIMARY KEY,
    nomT    VARCHAR(50),
    villeT  VARCHAR(50),
    numTelT VARCHAR(50)
);

CREATE TABLE CLIENT
(
    numClient       VARCHAR(50) PRIMARY KEY,
    nomClient       VARCHAR(50),
    villeClient     VARCHAR(50),
    contactClient   VARCHAR(50),
    categorieClient VARCHAR(50)
);

CREATE TABLE appel
(
    numAppel        VARCHAR(50) PRIMARY KEY,
    dateAppel       VARCHAR(50),
    descriptifAppel VARCHAR(50),
    etatAppel       VARCHAR(50),
    typeAppel       VARCHAR(50),
    numClient       VARCHAR(50) REFERENCES CLIENT (numClient) NOT NULL
);

CREATE TABLE cahier
(
    numCahier     VARCHAR(50) PRIMARY KEY,
    dateCahier    VARCHAR(50),
    montantCahier VARCHAR(50),
    numClient     VARCHAR(50) REFERENCES CLIENT (numClient),
    numAppel      VARCHAR(50) REFERENCES appel (numAppel)
);

CREATE TABLE employe
(
    numEmployer    VARCHAR(50) PRIMARY KEY,
    nomEmployer    VARCHAR(50),
    prenomEmployer VARCHAR(50),
    roleEmployer   VARCHAR(50),
    logEmployer    VARCHAR(50)
);

CREATE TABLE historique
(
    dateArriver DATE PRIMARY KEY
);

CREATE TABLE filiale
(
    numFilliale    VARCHAR(50) PRIMARY KEY,
    nomFiliale     VARCHAR(50),
    nomRespFiliale VARCHAR(50),
    telResp        VARCHAR(50)
);

CREATE TABLE ligne_cahier
(
    numCahier                VARCHAR(50) REFERENCES cahier (numCahier) NOT NULL,
    numeroLigne              VARCHAR(50)                               NOT NULL,
    PRIMARY KEY (numCahier, numeroLigne),
    descriptionArticleCahier VARCHAR(50)
);

CREATE TABLE integre
(
    codeT       VARCHAR(50) REFERENCES agence (codeT)        NOT NULL,
    codeT_1     VARCHAR(50) REFERENCES agence (codeT)        NOT NULL,
    numEmployer VARCHAR(50) REFERENCES employe (numEmployer) NOT NULL,
    PRIMARY KEY (codeT, codeT_1, numEmployer)
);

CREATE TABLE convoquer
(
    numCahier   VARCHAR(50) REFERENCES cahier (numCahier)    NOT NULL,
    numEmployer VARCHAR(50) REFERENCES employe (numEmployer) NOT NULL,
    PRIMARY KEY (numCahier, numEmployer)
);

CREATE TABLE embaucher
(
    numEmployer VARCHAR(50) REFERENCES employe (numEmployer) NOT NULL,
    dateArriver DATE REFERENCES historique (dateArriver)     NOT NULL,
    numFilliale VARCHAR(50) REFERENCES filiale (numFilliale) NOT NULL,
    PRIMARY KEY (numEmployer, dateArriver, numFilliale),
    dateSortie  VARCHAR(50)
);

insert into agence
values ('T1', 'Toulouse', 'Toulouse', '0567891234')
     , ('T2', 'Toulouse', 'Toulouse', '0567891234')
     , ('T3', 'Toulouse', 'Toulouse', '0567891234');
insert into filiale
values ('F1', 'filiale1', 'resp1', '0567891234')
     , ('F2', 'filiale2', 'resp2', '0567891234')
     , ('F3', 'filiale3', 'resp3', '0567891234');
insert into historique
values ('2020-03-24')
     , ('2020-03-25')
     , ('2020-03-26');
insert into embaucher
values ('E1', '2020-03-24', 'F1', '2020-03-25')
     , ('E2', '2020-03-24', 'F2', '2020-03-25')
     , ('E3', '2020-03-24', 'F3', '2020-03-25');
