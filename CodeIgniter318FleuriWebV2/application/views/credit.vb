public class credit 
	private monMontant as double
	private maDuree as double 
	private monTaux as double
	private maMensualite as double 
	sub new()
	end sub
	sub new(montant as double,duree as double,taux as double,mensualite as double){
		monMontant=montant
		maDuree=duree
		monTaux=taux
		maMensualite=mensualite
	}