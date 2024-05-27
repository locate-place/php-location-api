<?php

/*
 * This file is part of the twelvepics-com/php-location-api project.
 *
 * (c) Björn Hempel <https://www.hempel.li/>
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Constants\DB;

/**
 * Class FeatureCodeToId
 *
 * Build from Query:
 * -----------------
 * SELECT code, id
 * FROM "feature_code"
 * ORDER BY code
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-05-27)
 * @since 0.1.0 (2024-05-27) First version.
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class FeatureCodeToId
{
    final public const ADM1 = 107;
    final public const ADM1H = 287;
    final public const ADM2 = 104;
    final public const ADM2H = 189;
    final public const ADM3 = 11;
    final public const ADM3H = 73;
    final public const ADM4 = 68;
    final public const ADM4H = 70;
    final public const ADM5 = 119;
    final public const ADM5H = 618;
    final public const ADMD = 24;
    final public const ADMDH = 141;
    final public const ADMF = 221;
    final public const AGRC = 270;
    final public const AGRF = 182;
    final public const AIRB = 204;
    final public const AIRF = 198;
    final public const AIRH = 208;
    final public const AIRP = 199;
    final public const AIRQ = 201;
    final public const AIRS = 408;
    final public const AIRT = 423;
    final public const AMTH = 305;
    final public const AMUS = 246;
    final public const ANCH = 179;
    final public const ANS = 59;
    final public const APNU = 637;
    final public const AQC = 294;
    final public const ARCH = 299;
    final public const ARCHV = 308;
    final public const AREA = 37;
    final public const ART = 399;
    final public const ASPH = 642;
    final public const ASTR = 296;
    final public const ASYL = 375;
    final public const ATHF = 223;
    final public const ATM = 467;
    final public const ATOL = 439;
    final public const BANK = 229;
    final public const BAR = 84;
    final public const BAY = 56;
    final public const BAYS = 561;
    final public const BCH = 92;
    final public const BCHS = 320;
    final public const BCN = 338;
    final public const BDG = 170;
    final public const BDGQ = 323;
    final public const BDLD = 370;
    final public const BDLU = 385;
    final public const BGHT = 100;
    final public const BKSU = 499;
    final public const BLDA = 378;
    final public const BLDG = 65;
    final public const BLDO = 231;
    final public const BLDR = 419;
    final public const BLHL = 611;
    final public const BLOW = 645;
    final public const BNCH = 397;
    final public const BNK = 83;
    final public const BNKR = 454;
    final public const BNKU = 387;
    final public const BNKX = 473;
    final public const BOG = 80;
    final public const BP = 343;
    final public const BRKS = 316;
    final public const BRKW = 425;
    final public const BSND = 460;
    final public const BSNP = 647;
    final public const BSNU = 384;
    final public const BSTN = 505;
    final public const BTL = 412;
    final public const BTYD = 417;
    final public const BUR = 480;
    final public const BUSH = 506;
    final public const BUSTN = 181;
    final public const BUSTP = 254;
    final public const BUTE = 374;
    final public const CAPE = 106;
    final public const CAPG = 549;
    final public const CARN = 504;
    final public const CAVE = 45;
    final public const CFT = 328;
    final public const CH = 111;
    final public const CHN = 8;
    final public const CHNL = 177;
    final public const CHNM = 150;
    final public const CHNN = 488;
    final public const CLDA = 479;
    final public const CLF = 43;
    final public const CLG = 373;
    final public const CMN = 203;
    final public const CMP = 245;
    final public const CMPL = 453;
    final public const CMPLA = 344;
    final public const CMPMN = 565;
    final public const CMPO = 605;
    final public const CMPQ = 158;
    final public const CMPRF = 300;
    final public const CMTY = 215;
    final public const CNFL = 330;
    final public const CNL = 6;
    final public const CNLA = 266;
    final public const CNLB = 613;
    final public const CNLD = 409;
    final public const CNLI = 525;
    final public const CNLN = 186;
    final public const CNLQ = 331;
    final public const CNLSB = 529;
    final public const CNLX = 144;
    final public const CNS = 451;
    final public const CNYN = 411;
    final public const CNYU = 386;
    final public const COLF = 533;
    final public const COMC = 269;
    final public const CONE = 438;
    final public const COVE = 72;
    final public const CRDR = 639;
    final public const CRKT = 513;
    final public const CRNT = 493;
    final public const CRQ = 134;
    final public const CRQS = 575;
    final public const CRRL = 368;
    final public const CRSU = 612;
    final public const CRTR = 138;
    final public const CSNO = 67;
    final public const CST = 470;
    final public const CSTL = 42;
    final public const CSTM = 194;
    final public const CSWY = 209;
    final public const CTHSE = 306;
    final public const CTRA = 458;
    final public const CTRB = 276;
    final public const CTRCM = 252;
    final public const CTRF = 268;
    final public const CTRM = 174;
    final public const CTRR = 213;
    final public const CTRS = 403;
    final public const CUET = 531;
    final public const CULT = 226;
    final public const CUTF = 583;
    final public const CVNT = 153;
    final public const DAM = 120;
    final public const DAMQ = 396;
    final public const DAMSB = 667;
    final public const DARY = 326;
    final public const DCK = 86;
    final public const DCKB = 503;
    final public const DCKD = 447;
    final public const DCKY = 430;
    final public const DEPU = 509;
    final public const DEVH = 377;
    final public const DIKE = 523;
    final public const DIP = 315;
    final public const DLTA = 486;
    final public const DOMG = 603;
    final public const DPOF = 260;
    final public const DPR = 96;
    final public const DPRG = 601;
    final public const DSRT = 424;
    final public const DTCH = 30;
    final public const DTCHD = 518;
    final public const DTCHI = 364;
    final public const DTCHM = 620;
    final public const DUNE = 75;
    final public const DVD = 379;
    final public const EDGU = 659;
    final public const ERG = 593;
    final public const ESCU = 654;
    final public const EST = 95;
    final public const ESTO = 578;
    final public const ESTR = 558;
    final public const ESTSG = 646;
    final public const ESTT = 591;
    final public const ESTX = 311;
    final public const ESTY = 172;
    final public const FAN = 623;
    final public const FANU = 652;
    final public const FCL = 152;
    final public const FISH = 365;
    final public const FJD = 433;
    final public const FLD = 114;
    final public const FLDI = 544;
    final public const FLLS = 112;
    final public const FLLSX = 448;
    final public const FLTM = 482;
    final public const FLTT = 7;
    final public const FLTU = 402;
    final public const FNDY = 248;
    final public const FORD = 502;
    final public const FRM = 18;
    final public const FRMQ = 139;
    final public const FRMS = 161;
    final public const FRMT = 64;
    final public const FRST = 16;
    final public const FRSTF = 372;
    final public const FRZU = 649;
    final public const FSR = 527;
    final public const FT = 63;
    final public const FURU = 633;
    final public const FY = 228;
    final public const FYT = 483;
    final public const GAP = 129;
    final public const GAPU = 636;
    final public const GASF = 498;
    final public const GATE = 253;
    final public const GDN = 178;
    final public const GHAT = 592;
    final public const GHSE = 187;
    final public const GLCR = 94;
    final public const GLYU = 568;
    final public const GOSP = 608;
    final public const GOVL = 283;
    final public const GRAZ = 22;
    final public const GRGE = 109;
    final public const GROVE = 494;
    final public const GRSLD = 346;
    final public const GRVC = 648;
    final public const GRVE = 301;
    final public const GRVO = 664;
    final public const GRVP = 485;
    final public const GRVPN = 426;
    final public const GULF = 443;
    final public const GVL = 468;
    final public const GYSR = 437;
    final public const HBR = 21;
    final public const HBRX = 90;
    final public const HDLD = 159;
    final public const HERM = 329;
    final public const HLL = 19;
    final public const HLLS = 17;
    final public const HLLU = 658;
    final public const HLSU = 619;
    final public const HLT = 297;
    final public const HMCK = 407;
    final public const HMDA = 582;
    final public const HMSD = 322;
    final public const HOLU = 393;
    final public const HSE = 110;
    final public const HSEC = 93;
    final public const HSP = 97;
    final public const HSPC = 40;
    final public const HSPD = 264;
    final public const HSPL = 345;
    final public const HSTS = 133;
    final public const HTH = 38;
    final public const HTL = 118;
    final public const HUT = 29;
    final public const HUTS = 243;
    final public const INDS = 127;
    final public const INLT = 98;
    final public const INLTQ = 475;
    final public const INSM = 309;
    final public const INTF = 548;
    final public const ISL = 49;
    final public const ISLET = 427;
    final public const ISLF = 341;
    final public const ISLM = 630;
    final public const ISLS = 140;
    final public const ISLT = 142;
    final public const ISLX = 149;
    final public const ISTH = 131;
    final public const ITTR = 125;
    final public const JTY = 210;
    final public const KNLU = 621;
    final public const KRST = 361;
    final public const LAND = 616;
    final public const LAVA = 420;
    final public const LBED = 60;
    final public const LCTY = 54;
    final public const LDGU = 459;
    final public const LDNG = 286;
    final public const LEPC = 537;
    final public const LEV = 392;
    final public const LEVU = 487;
    final public const LGN = 51;
    final public const LGNS = 535;
    final public const LGNX = 546;
    final public const LIBR = 261;
    final public const LK = 23;
    final public const LKC = 205;
    final public const LKI = 263;
    final public const LKN = 542;
    final public const LKNI = 508;
    final public const LKO = 196;
    final public const LKOI = 552;
    final public const LKS = 66;
    final public const LKSB = 669;
    final public const LKSC = 610;
    final public const LKSI = 587;
    final public const LKSN = 559;
    final public const LKX = 148;
    final public const LNDF = 391;
    final public const LOCK = 185;
    final public const LTER = 429;
    final public const LTHSE = 291;
    final public const MALL = 237;
    final public const MAR = 143;
    final public const MDW = 89;
    final public const MESA = 434;
    final public const MFG = 247;
    final public const MFGB = 154;
    final public const MFGC = 432;
    final public const MFGCU = 607;
    final public const MFGLM = 629;
    final public const MFGM = 335;
    final public const MFGN = 521;
    final public const MFGPH = 622;
    final public const MFGQ = 271;
    final public const MFGSG = 564;
    final public const MGV = 562;
    final public const MILB = 200;
    final public const MKT = 218;
    final public const ML = 157;
    final public const MLM = 551;
    final public const MLO = 579;
    final public const MLSG = 595;
    final public const MLSGQ = 450;
    final public const MLSW = 333;
    final public const MLWND = 222;
    final public const MLWTR = 176;
    final public const MN = 130;
    final public const MNA = 352;
    final public const MNAU = 422;
    final public const MNC = 259;
    final public const MNCR = 657;
    final public const MNCU = 596;
    final public const MND = 123;
    final public const MNDU = 410;
    final public const MNFE = 554;
    final public const MNMT = 99;
    final public const MNN = 609;
    final public const MNQ = 122;
    final public const MNQR = 173;
    final public const MOLE = 421;
    final public const MOOR = 77;
    final public const MRN = 497;
    final public const MRSH = 4;
    final public const MRSHN = 514;
    final public const MSQE = 312;
    final public const MSSN = 288;
    final public const MSSNQ = 457;
    final public const MSTY = 117;
    final public const MT = 9;
    final public const MTRO = 281;
    final public const MTS = 26;
    final public const MTU = 665;
    final public const MUS = 214;
    final public const MVA = 146;
    final public const NKM = 624;
    final public const NOV = 539;
    final public const NRWS = 168;
    final public const NSY = 362;
    final public const NTK = 599;
    final public const NTKS = 655;
    final public const NVB = 511;
    final public const OAS = 526;
    final public const OBPT = 171;
    final public const OBS = 121;
    final public const OBSR = 302;
    final public const OCH = 135;
    final public const OCN = 660;
    final public const OILF = 389;
    final public const OILJ = 478;
    final public const OILP = 466;
    final public const OILQ = 606;
    final public const OILR = 357;
    final public const OILT = 472;
    final public const OILW = 444;
    final public const OPRA = 230;
    final public const OVF = 369;
    final public const PAL = 126;
    final public const PAN = 227;
    final public const PANS = 641;
    final public const PASS = 108;
    final public const PCL = 670;
    final public const PCLD = 547;
    final public const PCLF = 668;
    final public const PCLH = 290;
    final public const PCLI = 175;
    final public const PCLIX = 671;
    final public const PCLS = 656;
    final public const PEAT = 350;
    final public const PEN = 36;
    final public const PENX = 500;
    final public const PGDA = 358;
    final public const PIER = 188;
    final public const PK = 25;
    final public const PKLT = 255;
    final public const PKS = 319;
    final public const PKSU = 524;
    final public const PLAT = 202;
    final public const PLATX = 380;
    final public const PLDR = 58;
    final public const PLN = 155;
    final public const PLNU = 640;
    final public const PLNX = 586;
    final public const PLTU = 615;
    final public const PMPO = 428;
    final public const PMPW = 267;
    final public const PND = 33;
    final public const PNDI = 363;
    final public const PNDN = 522;
    final public const PNDNI = 594;
    final public const PNDS = 180;
    final public const PNDSF = 216;
    final public const PNDSI = 589;
    final public const PNDSN = 560;
    final public const PNLU = 666;
    final public const PO = 219;
    final public const POOL = 105;
    final public const POOLI = 492;
    final public const PP = 257;
    final public const PPL = 14;
    final public const PPLA = 81;
    final public const PPLA2 = 62;
    final public const PPLA3 = 27;
    final public const PPLA4 = 15;
    final public const PPLA5 = 347;
    final public const PPLC = 192;
    final public const PPLCH = 340;
    final public const PPLF = 145;
    final public const PPLG = 275;
    final public const PPLH = 32;
    final public const PPLL = 20;
    final public const PPLQ = 71;
    final public const PPLR = 249;
    final public const PPLS = 160;
    final public const PPLW = 31;
    final public const PPLX = 2;
    final public const PPQ = 293;
    final public const PRK = 69;
    final public const PRKGT = 317;
    final public const PRKHQ = 413;
    final public const PRMN = 376;
    final public const PRN = 235;
    final public const PRNJ = 540;
    final public const PRNQ = 495;
    final public const PROM = 116;
    final public const PRSH = 336;
    final public const PRT = 82;
    final public const PRVU = 638;
    final public const PS = 163;
    final public const PSH = 272;
    final public const PSN = 490;
    final public const PSTB = 240;
    final public const PSTC = 324;
    final public const PSTP = 570;
    final public const PT = 52;
    final public const PTGE = 366;
    final public const PTS = 571;
    final public const PYR = 581;
    final public const PYRS = 644;
    final public const QCKS = 643;
    final public const QUAY = 169;
    final public const RCH = 435;
    final public const RD = 102;
    final public const RDA = 278;
    final public const RDB = 359;
    final public const RDCR = 401;
    final public const RDCUT = 477;
    final public const RDGB = 507;
    final public const RDGE = 41;
    final public const RDGG = 602;
    final public const RDGU = 273;
    final public const RDIN = 590;
    final public const RDJCT = 191;
    final public const RDST = 13;
    final public const RDSU = 604;
    final public const RECG = 232;
    final public const RECR = 206;
    final public const REG = 532;
    final public const RES = 103;
    final public const RESA = 244;
    final public const RESF = 292;
    final public const RESH = 414;
    final public const RESN = 87;
    final public const RESP = 580;
    final public const REST = 147;
    final public const RESV = 256;
    final public const RESW = 339;
    final public const RET = 277;
    final public const RF = 115;
    final public const RFC = 563;
    final public const RFSU = 484;
    final public const RFU = 382;
    final public const RFX = 635;
    final public const RGN = 12;
    final public const RGNE = 265;
    final public const RGNH = 381;
    final public const RGNL = 151;
    final public const RHSE = 242;
    final public const RISU = 614;
    final public const RJCT = 251;
    final public const RK = 46;
    final public const RKFL = 282;
    final public const RKRY = 445;
    final public const RKS = 55;
    final public const RLG = 274;
    final public const RLGR = 258;
    final public const RNCH = 280;
    final public const RNGA = 491;
    final public const RPDS = 354;
    final public const RR = 241;
    final public const RRQ = 462;
    final public const RSD = 464;
    final public const RSGNL = 501;
    final public const RSRT = 132;
    final public const RSTN = 1;
    final public const RSTNQ = 48;
    final public const RSTP = 35;
    final public const RSTPQ = 53;
    final public const RSV = 44;
    final public const RSVI = 557;
    final public const RSVT = 398;
    final public const RTE = 555;
    final public const RUIN = 57;
    final public const RVN = 47;
    final public const RYD = 10;
    final public const SALT = 520;
    final public const SAND = 101;
    final public const SBED = 431;
    final public const SBKH = 519;
    final public const SCH = 78;
    final public const SCHA = 156;
    final public const SCHC = 304;
    final public const SCHL = 536;
    final public const SCHM = 327;
    final public const SCHN = 617;
    final public const SCHT = 367;
    final public const SCNU = 404;
    final public const SCRB = 474;
    final public const SCRP = 137;
    final public const SCSU = 550;
    final public const SD = 442;
    final public const SDL = 165;
    final public const SEA = 406;
    final public const SECP = 576;
    final public const SHFU = 632;
    final public const SHLU = 388;
    final public const SHOL = 61;
    final public const SHOR = 489;
    final public const SHPF = 543;
    final public const SHRN = 351;
    final public const SHSE = 262;
    final public const SHSU = 383;
    final public const SINK = 124;
    final public const SLCE = 211;
    final public const SLID = 371;
    final public const SLP = 50;
    final public const SLPU = 553;
    final public const SMSU = 650;
    final public const SMU = 440;
    final public const SNOW = 456;
    final public const SNTR = 164;
    final public const SPA = 236;
    final public const SPIT = 162;
    final public const SPLY = 471;
    final public const SPNG = 74;
    final public const SPNS = 577;
    final public const SPNT = 436;
    final public const SPRU = 651;
    final public const SPUR = 167;
    final public const SQR = 238;
    final public const ST = 76;
    final public const STBL = 342;
    final public const STDM = 217;
    final public const STKR = 541;
    final public const STLMT = 465;
    final public const STM = 3;
    final public const STMA = 136;
    final public const STMB = 390;
    final public const STMC = 5;
    final public const STMD = 530;
    final public const STMH = 356;
    final public const STMI = 184;
    final public const STMIX = 600;
    final public const STMM = 207;
    final public const STMQ = 195;
    final public const STMS = 250;
    final public const STMSB = 355;
    final public const STMX = 88;
    final public const STNB = 298;
    final public const STNC = 452;
    final public const STNE = 395;
    final public const STNF = 34;
    final public const STNI = 303;
    final public const STNM = 224;
    final public const STNR = 234;
    final public const STNS = 441;
    final public const STNW = 446;
    final public const STPS = 481;
    final public const STRT = 113;
    final public const SWMP = 79;
    final public const SWT = 279;
    final public const SYG = 415;
    final public const SYSI = 567;
    final public const TAL = 496;
    final public const TERR = 631;
    final public const TERU = 566;
    final public const THTR = 225;
    final public const TMB = 325;
    final public const TMPL = 233;
    final public const TMTU = 653;
    final public const TNGU = 405;
    final public const TNKD = 332;
    final public const TNL = 349;
    final public const TNLC = 289;
    final public const TNLRD = 307;
    final public const TNLRR = 190;
    final public const TOLL = 538;
    final public const TOWR = 91;
    final public const TRAM = 334;
    final public const TRANT = 310;
    final public const TRB = 463;
    final public const TREE = 193;
    final public const TRGD = 625;
    final public const TRGU = 461;
    final public const TRIG = 469;
    final public const TRL = 166;
    final public const TRMO = 588;
    final public const TRNU = 534;
    final public const TRR = 360;
    final public const TUND = 528;
    final public const TWO = 476;
    final public const UNIP = 515;
    final public const UNIV = 220;
    final public const UPLD = 85;
    final public const USGE = 400;
    final public const VAL = 28;
    final public const VALG = 348;
    final public const VALS = 295;
    final public const VALU = 416;
    final public const VALX = 353;
    final public const VETF = 418;
    final public const VIN = 321;
    final public const VINS = 455;
    final public const VLC = 197;
    final public const VLSU = 661;
    final public const WAD = 573;
    final public const WADB = 663;
    final public const WADJ = 634;
    final public const WADM = 626;
    final public const WADS = 662;
    final public const WADX = 627;
    final public const WALL = 212;
    final public const WALLA = 517;
    final public const WEIR = 313;
    final public const WHRF = 285;
    final public const WHRL = 510;
    final public const WLL = 128;
    final public const WLLQ = 597;
    final public const WLLS = 628;
    final public const WRCK = 284;
    final public const WTLD = 337;
    final public const WTLDI = 598;
    final public const WTRC = 318;
    final public const WTRH = 545;
    final public const WTRW = 314;
    final public const ZN = 516;
    final public const ZNB = 584;
    final public const ZNF = 572;
    final public const ZOO = 39;
}
