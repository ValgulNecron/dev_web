drop database if exists exo2;

create database exo2;

use exo2;

create table Client
(
    Num    int auto_increment not null primary key,
    Prenom varchar(40),
    Nom    varchar(40),
    Email  varchar(40),
    Ville  varchar(40),
    CP     varchar(40)
);
create table Produit
(
    Num         int auto_increment not null primary key,
    Libelle     varchar(40),
    Prix        float,
    Description varchar(40),
    TauxTVA     float
);
create table Commande
(
    Num          int auto_increment not null primary key,
    DateCommande date,
    DatePaiement date,
    NumClient    int                not null,
    foreign key (NumClient) references Client (Num)
);
create table Composer
(
    NumCommande int not null,
    NumProduit  int not null,
    Quantite    int,
    foreign key (NumCommande) references Commande (Num),
    foreign key (NumProduit) references Produit (Num),
    primary key (NumCommande, NumProduit)
);
create table Livrer
(
    NumCommande int not null,
    Quantite    int,
    NumProduit  int not null,
    foreign key (NumCommande) references Commande (Num),
    foreign key (NumProduit) references Produit (Num),
    primary key (NumCommande, NumProduit)
);
drop trigger if exists verifLivraison;
DELIMITER $$
create trigger verifLivraison
    before insert
    on Livrer
    for each row
BEGIN
    if (select Composer.NumCommande and Composer.NumProduit
        from composer
        where Composer.NumCommande = NEW.NumCommande
          and Composer.NumProduit = NEW.NumProduit) is null then
        signal sqlstate '45000' set message_text = 'La quantité livrée ne peut être supérieure à la quantité commandée';
    end if;
end $$;
DELIMITER ;
drop trigger if exists verifUpdateLivraison;
DELIMITER $$
create trigger verifUpdateLivraison
    before update
    on Livrer
    for each row
BEGIN
    if (select Composer.NumCommande and Composer.NumProduit
        from composer
        where Composer.NumCommande = NEW.NumCommande
          and Composer.NumProduit = NEW.NumProduit) is null then
        signal sqlstate '45000' set message_text = 'La commande n''existe pas';
    end if;
    if (select Composer.Quantite
        from composer
        where Composer.NumCommande = NEW.NumCommande
          and Composer.NumProduit = NEW.NumProduit) < NEW.Quantite then
        signal sqlstate '45000' set message_text = 'La quantité est supérieure à celle commandée';
    end if;
end $$;
DELIMITER ;

insert into Client (Prenom, Nom, Email, Ville, CP)
values ('Jean', 'Dupont', 'test@test.com', 'Paris', '75000');
insert into produit (Libelle, Prix, Description, TauxTVA)
values ('Produit 1', 10, 'Description 1', 20),
       ('Produit 2', 20, 'Description 2', 20);
insert into commande(num, datecommande, datepaiement, numclient)
values (1, '2019-01-01', '2019-01-01', 1);
insert into composer(numcommande, numproduit, quantite)
values (1, 1, 2),
       (1, 2, 1);
insert into livrer(numcommande, numproduit, quantite)
values (1, 1, 2),
       (1, 2, 1);