Drop database if EXISTS entrepriseLegrand;
create database if not EXISTS entrepriseLegrand;

use entrepriseLegrand;

create table CLIENT
(
    NumClient       int primary key not null,
    NomClient       varchar(20),
    VilleClient     varchar(40),
    ContactClient   varchar(80),
    CategorieClient varchar(20)
);
create table EMPLOYE
(
    NumEmploye    int primary key not null,
    NomEmploye    varchar(20),
    PrenomEmploye varchar(20),
    RoleEmploye   varchar(20),
    LogEmploye    varchar(20),
    MdpEmploye    varchar(20)
);
Create table APPEL
(
    NumAppel         int primary key not null,
    DateAppel        date,
    DescriptionAppel varchar(80),
    EtatAppel        varchar(20),
    TypeAppel        varchar(20),
    NumClient        int,
    NumEmploye       int key references,
    foreign key (NumClient) references CLIENT (NumClient),
    foreign key (NumEmploye) references EMPLOYE (NumEmploye)
);
create table CAHIER
(
    NumCahier     int primary key not null,
    DateCahier    date,
    MontantCahier float,
    NumClient     int,
    NumEmploye    int,
    NumAppel      int,
    foreign key (NumClient) references CLIENT (NumClient),
    foreign key (NumEmploye) references EMPLOYE (NumEmploye),
    foreign key (NumAppel) references APPEL (NumAppel)
);
create table LIGNE_CAHIER
(
    NumLigneCahier           int not null,
    NumCahier                int,
    DescriptionArticleCahier varchar(80),
    foreign key (NumCahier) references CAHIER (NumCahier),
    primary key (NumLigneCahier, NumCahier)
);


