-- Stock recalculation from movements (VFP convention)
-- iddepoh = goods ENTERING deposit (adds), iddepod = goods LEAVING deposit (subtracts)
-- Run this if stock table gets out of sync with stockdet + stockcab

-- 1. Rebuild stock table
TRUNCATE TABLE stock;

INSERT INTO stock (iddepo, idprodu, idcodgusto, stock)
SELECT mov.iddepo, mov.idprodu, mov.idcodgusto, SUM(mov.net) AS stock
FROM (
    SELECT sc.iddepoh AS iddepo, sd.idprodu, sd.idcodgusto, sd.canti AS net
    FROM stockcab sc
    INNER JOIN stockdet sd ON sd.idstockcab = sc.idcabstock
    WHERE sc.iddepoh IS NOT NULL
    UNION ALL
    SELECT sc.iddepod AS iddepo, sd.idprodu, sd.idcodgusto, -sd.canti AS net
    FROM stockcab sc
    INNER JOIN stockdet sd ON sd.idstockcab = sc.idcabstock
    WHERE sc.iddepod IS NOT NULL
) mov
GROUP BY mov.iddepo, mov.idprodu, mov.idcodgusto
HAVING stock != 0;

-- 2. Recalculate producto.stocact
UPDATE producto p
LEFT JOIN (
    SELECT idprodu, COALESCE(SUM(stock), 0) AS total
    FROM stock
    GROUP BY idprodu
) s ON s.idprodu = p.idprodu
SET p.stocact = COALESCE(s.total, 0);

-- 3. Recalculate gustos.stockact
UPDATE gustos g
LEFT JOIN (
    SELECT idcodgusto, COALESCE(SUM(stock), 0) AS total
    FROM stock
    WHERE idcodgusto IS NOT NULL
    GROUP BY idcodgusto
) s ON s.idcodgusto = g.idcodgusto
SET g.stockact = COALESCE(s.total, 0);
