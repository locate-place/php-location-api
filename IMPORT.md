# Import examples

## Import data

The data comes from http://download.geonames.org/export/dump/
and are placed under `import/location`.

### Download command:

```bash
bin/console location:download "AD" && \
bin/console location:download "AT" && \
bin/console location:download "AL" && \
bin/console location:download "AX" && \
bin/console location:download "BA" && \
bin/console location:download "BE" && \
bin/console location:download "BG" && \
bin/console location:download "BR" && \
bin/console location:download "BY" && \
bin/console location:download "CA" && \
bin/console location:download "CA" && \
bin/console location:download "CH" && \
bin/console location:download "CI" && \
bin/console location:download "CN" && \
bin/console location:download "CY" && \
bin/console location:download "CZ" && \
bin/console location:download "DE" && \
bin/console location:download "DK" && \
bin/console location:download "EE" && \
bin/console location:download "ES" && \
bin/console location:download "FI" && \
bin/console location:download "FO" && \
bin/console location:download "FR" && \
bin/console location:download "GB" && \
bin/console location:download "GI" && \
bin/console location:download "GR" && \
bin/console location:download "HR" && \
bin/console location:download "IE" && \
bin/console location:download "IN" && \
bin/console location:download "IS" && \
bin/console location:download "IT" && \
bin/console location:download "JP" && \
bin/console location:download "LI" && \
bin/console location:download "LU" && \
bin/console location:download "MC" && \
bin/console location:download "MX" && \
bin/console location:download "NL" && \
bin/console location:download "NO" && \
bin/console location:download "PL" && \
bin/console location:download "PT" && \
bin/console location:download "RU" && \
bin/console location:download "SE" && \
bin/console location:download "SI" && \
bin/console location:download "TR" && \
bin/console location:download "UA" && \
bin/console location:download "US"
```

### Import command:

```bash
bin/console location:import import/location/AD.txt && \
bin/console location:import import/location/AL.txt && \
bin/console location:import import/location/AT.txt && \
bin/console location:import import/location/AX.txt && \
bin/console location:import import/location/BA.txt && \
bin/console location:import import/location/BE.txt && \
bin/console location:import import/location/BG.txt && \
bin/console location:import import/location/BR.txt && \
bin/console location:import import/location/BY.txt && \
bin/console location:import import/location/CA.txt && \
bin/console location:import import/location/CA.txt && \
bin/console location:import import/location/CH.txt && \
bin/console location:import import/location/CI.txt && \
bin/console location:import import/location/CN.txt && \
bin/console location:import import/location/CY.txt && \
bin/console location:import import/location/CZ.txt && \
bin/console location:import import/location/DE.txt && \
bin/console location:import import/location/DK.txt && \
bin/console location:import import/location/EE.txt && \
bin/console location:import import/location/ES.txt && \
bin/console location:import import/location/FI.txt && \
bin/console location:import import/location/FO.txt && \
bin/console location:import import/location/FR.txt && \
bin/console location:import import/location/GB.txt && \
bin/console location:import import/location/GI.txt && \
bin/console location:import import/location/GR.txt && \
bin/console location:import import/location/HR.txt && \
bin/console location:import import/location/IE.txt && \
bin/console location:import import/location/IN.txt && \
bin/console location:import import/location/IS.txt && \
bin/console location:import import/location/IT.txt && \
bin/console location:import import/location/JP.txt && \
bin/console location:import import/location/LI.txt && \
bin/console location:import import/location/LU.txt && \
bin/console location:import import/location/MC.txt && \
bin/console location:import import/location/MX.txt && \
bin/console location:import import/location/NL.txt && \
bin/console location:import import/location/NO.txt && \
bin/console location:import import/location/PL.txt && \
bin/console location:import import/location/PT.txt && \
bin/console location:import import/location/RU.txt && \
bin/console location:import import/location/SE.txt && \
bin/console location:import import/location/SI.txt && \
bin/console location:import import/location/TR.txt && \
bin/console location:import import/location/UA.txt && \
bin/console location:import import/location/US.txt
```

Takes about 20 seconds for 10000 lines. The country DE for
example with 200000 needs about seven minutes to import.
Depending on the performance of the system used and the
amounts of data in the table.
