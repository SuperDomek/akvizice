SELECT CZU50.CAR_KODY_SIGNATURY.ADM_REC, CZU50.CAR_KODY_SIGNATURY.SIGNATURA,CZU01.TITULY.NAZEV,CZU01.TITULY.ISBN,CZU01.TITULY.AUTOR
FROM CZU50.CAR_KODY_SIGNATURY JOIN CZU01.TITULY ON CZU50.CAR_KODY_SIGNATURY.BIB_REC = CZU01.TITULY.BIB_REC