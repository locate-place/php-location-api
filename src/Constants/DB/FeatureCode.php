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

use App\Constants\DB\Base\BaseFeature;
use App\Constants\Key\KeyArray;
use App\Constants\Language\Domain;
use LogicException;

/**
 * Class FeatureCode
 *
 * @author Björn Hempel <bjoern@hempel.li>
 * @version 0.1.0 (2024-01-07)
 * @since 0.1.0 (2024-01-07) First version.
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 *
 * @see http://www.geonames.org/export/codes.html
 */
class FeatureCode extends BaseFeature
{
    /* A codes → country, state, region, ... */
    final public const ADM1  = 'ADM1';  /* first-order administrative division a primary administrative division of a country, such as a state in the United States */
    final public const ADM1H = 'ADM1H'; /* historical first-order administrative division a former first-order administrative division */
    final public const ADM2  = 'ADM2';  /* second-order administrative division a subdivision of a first-order administrative division */
    final public const ADM2H = 'ADM2H'; /* historical second-order administrative division a former second-order administrative division */
    final public const ADM3  = 'ADM3';  /* third-order administrative division a subdivision of a second-order administrative division */
    final public const ADM3H = 'ADM3H'; /* historical third-order administrative division a former third-order administrative division */
    final public const ADM4  = 'ADM4';  /* fourth-order administrative division a subdivision of a third-order administrative division */
    final public const ADM4H = 'ADM4H'; /* historical fourth-order administrative division a former fourth-order administrative division */
    final public const ADM5  = 'ADM5';  /* fifth-order administrative division a subdivision of a fourth-order administrative division */
    final public const ADM5H = 'ADM5H'; /* historical fifth-order administrative division a former fifth-order administrative division */
    final public const ADMD  = 'ADMD';  /* administrative division an administrative division of a country, undifferentiated as to administrative level */
    final public const ADMDH = 'ADMDH'; /* historical administrative division a former administrative division of a political entity, undifferentiated as to administrative level */
    final public const LTER  = 'LTER';  /* leased area a tract of land leased to another country, usually for military installations */
    final public const PCL   = 'PCL';   /* political entity */
    final public const PCLD  = 'PCLD';  /* dependent political entity */
    final public const PCLF  = 'PCLF';  /* freely associated state */
    final public const PCLH  = 'PCLH';  /* historical political entity a former political entity */
    final public const PCLI  = 'PCLI';  /* independent political entity */
    final public const PCLIX = 'PCLIX'; /* section of independent political entity */
    final public const PCLS  = 'PCLS';  /* semi-independent political entity */
    final public const PRSH  = 'PRSH';  /* parish an ecclesiastical district */
    final public const TERR  = 'TERR';  /* territory */
    final public const ZN    = 'ZN';    /* zone */
    final public const ZNB   = 'ZNB';   /* buffer zone a zone recognized as a buffer between two nations in which military presence is minimal or absent */

    /* H codes → Streams, Lakes, etc. */
    final public const AIRS  = 'AIRS';  /* seaplane landing area a place on a waterbody where floatplanes land and take off */
    final public const ANCH  = 'ANCH';  /* anchorage an area where vessels may anchor */
    final public const BAY   = 'BAY';   /* bay a coastal indentation between two capes or headlands, larger than a cove but smaller than a gulf */
    final public const BAYS  = 'BAYS';  /* bays coastal indentations between two capes or headlands, larger than a cove but smaller than a gulf */
    final public const BGHT  = 'BGHT';  /* bight(s) an open body of water forming a slight recession in a coastline */
    final public const BNK   = 'BNK';   /* bank(s) an elevation, typically located on a shelf, over which the depth of water is relatively shallow but sufficient for most surface navigation */
    final public const BNKR  = 'BNKR';  /* stream bank a sloping margin of a stream channel which normally confines the stream to its channel on land */
    final public const BNKX  = 'BNKX';  /* section of bank */
    final public const BOG   = 'BOG';   /* bog(s) a wetland characterized by peat forming sphagnum moss, sedge, and other acid-water plants */
    final public const CAPG  = 'CAPG';  /* icecap a dome-shaped mass of glacial ice covering an area of mountain summits or other high lands; smaller than an ice sheet */
    final public const CHN   = 'CHN';   /* channel the deepest part of a stream, bay, lagoon, or strait, through which the main current flows */
    final public const CHNL  = 'CHNL';  /* lake channel(s) that part of a lake having water deep enough for navigation between islands, shoals, etc. */
    final public const CHNM  = 'CHNM';  /* marine channel that part of a body of water deep enough for navigation through an area otherwise not suitable */
    final public const CHNN  = 'CHNN';  /* navigation channel a buoyed channel of sufficient depth for the safe navigation of vessels */
    final public const CNFL  = 'CNFL';  /* confluence a place where two or more streams or intermittent streams flow together */
    final public const CNL   = 'CNL';   /* canal an artificial watercourse */
    final public const CNLA  = 'CNLA';  /* aqueduct a conduit used to carry water */
    final public const CNLB  = 'CNLB';  /* canal bend a conspicuously curved or bent section of a canal */
    final public const CNLD  = 'CNLD';  /* drainage canal an artificial waterway carrying water away from a wetland or from drainage ditches */
    final public const CNLI  = 'CNLI';  /* irrigation canal a canal which serves as a main conduit for irrigation water */
    final public const CNLN  = 'CNLN';  /* navigation canal(s) a watercourse constructed for navigation of vessels */
    final public const CNLQ  = 'CNLQ';  /* abandoned canal */
    final public const CNLSB = 'CNLSB'; /* underground irrigation canal(s) a gently inclined underground tunnel bringing water for irrigation from aquifers */
    final public const CNLX  = 'CNLX';  /* section of canal */
    final public const COVE  = 'COVE';  /* cove(s) a small coastal indentation, smaller than a bay */
    final public const CRKT  = 'CRKT';  /* tidal creek(s) a meandering channel in a coastal wetland subject to bi-directional tidal currents */
    final public const CRNT  = 'CRNT';  /* current a horizontal flow of water in a given direction with uniform velocity */
    final public const CUTF  = 'CUTF';  /* cutoff a channel formed as a result of a stream cutting through a meander neck */
    final public const DCK   = 'DCK';   /* dock(s) a waterway between two piers, or cut into the land for the berthing of ships */
    final public const DCKB  = 'DCKB';  /* docking basin a part of a harbor where ships dock */
    final public const DOMG  = 'DOMG';  /* icecap dome a comparatively elevated area on an icecap */
    final public const DPRG  = 'DPRG';  /* icecap depression a comparatively depressed area on an icecap */
    final public const DTCH  = 'DTCH';  /* ditch a small artificial watercourse dug for draining or irrigating the land */
    final public const DTCHD = 'DTCHD'; /* drainage ditch a ditch which serves to drain the land */
    final public const DTCHI = 'DTCHI'; /* irrigation ditch a ditch which serves to distribute irrigation water */
    final public const DTCHM = 'DTCHM'; /* ditch mouth(s) an area where a drainage ditch enters a lagoon, lake or bay */
    final public const ESTY  = 'ESTY';  /* estuary a funnel-shaped stream mouth or embayment where fresh water mixes with sea water under tidal influences */
    final public const FISH  = 'FISH';  /* fishing area a fishing ground, bank or area where fishermen go to catch fish */
    final public const FJD   = 'FJD';   /* fjord a long, narrow, steep-walled, deep-water arm of the sea at high latitudes, usually along mountainous coasts */
    final public const FJDS  = 'FJDS';  /* fjords long, narrow, steep-walled, deep-water arms of the sea at high latitudes, usually along mountainous coasts */
    final public const FLLS  = 'FLLS';  /* waterfall(s) a perpendicular or very steep descent of the water of a stream */
    final public const FLLSX = 'FLLSX'; /* section of waterfall(s) */
    final public const FLTM  = 'FLTM';  /* mud flat(s) a relatively level area of mud either between high and low tide lines, or subject to flooding */
    final public const FLTT  = 'FLTT';  /* tidal flat(s) a large flat area of mud or sand attached to the shore and alternately covered and uncovered by the tide */
    final public const GLCR  = 'GLCR';  /* glacier(s) a mass of ice, usually at high latitudes or high elevations, with sufficient thickness to flow away from the source area in lobes, tongues, or masses */
    final public const GULF  = 'GULF';  /* gulf a large recess in the coastline, larger than a bay */
    final public const GYSR  = 'GYSR';  /* geyser a type of hot spring with intermittent eruptions of jets of hot water and steam */
    final public const HBR   = 'HBR';   /* harbor(s) a haven or space of deep water so sheltered by the adjacent land as to afford a safe anchorage for ships */
    final public const HBRX  = 'HBRX';  /* section of harbor */
    final public const INLT  = 'INLT';  /* inlet a narrow waterway extending into the land, or connecting a bay or lagoon with a larger body of water */
    final public const INLTQ = 'INLTQ'; /* former inlet an inlet which has been filled in, or blocked by deposits */
    final public const LBED  = 'LBED';  /* lake bed(s) a dried up or drained area of a former lake */
    final public const LGN   = 'LGN';   /* lagoon a shallow coastal waterbody, completely or partly separated from a larger body of water by a barrier island, coral reef or other depositional feature */
    final public const LGNS  = 'LGNS';  /* lagoons shallow coastal waterbodies, completely or partly separated from a larger body of water by a barrier island, coral reef or other depositional feature */
    final public const LGNX  = 'LGNX';  /* section of lagoon */
    final public const LK    = 'LK';    /* lake a large inland body of standing water */
    final public const LKC   = 'LKC';   /* crater lake a lake in a crater or caldera */
    final public const LKI   = 'LKI';   /* intermittent lake */
    final public const LKN   = 'LKN';   /* salt lake an inland body of salt water with no outlet */
    final public const LKNI  = 'LKNI';  /* intermittent salt lake */
    final public const LKO   = 'LKO';   /* oxbow lake a crescent-shaped lake commonly found adjacent to meandering streams */
    final public const LKOI  = 'LKOI';  /* intermittent oxbow lake */
    final public const LKS   = 'LKS';   /* lakes large inland bodies of standing water */
    final public const LKSB  = 'LKSB';  /* underground lake a standing body of water in a cave */
    final public const LKSC  = 'LKSC';  /* crater lakes lakes in a crater or caldera */
    final public const LKSI  = 'LKSI';  /* intermittent lakes */
    final public const LKSN  = 'LKSN';  /* salt lakes inland bodies of salt water with no outlet */
    final public const LKSNI = 'LKSNI'; /* intermittent salt lakes */
    final public const LKX   = 'LKX';   /* section of lake */
    final public const MFGN  = 'MFGN';  /* salt evaporation ponds diked salt ponds used in the production of solar evaporated salt */
    final public const MGV   = 'MGV';   /* mangrove swamp a tropical tidal mud flat characterized by mangrove vegetation */
    final public const MOOR  = 'MOOR';  /* moor(s) an area of open ground overlaid with wet peaty soils */
    final public const MRSH  = 'MRSH';  /* marsh(es) a wetland dominated by grass-like vegetation */
    final public const MRSHN = 'MRSHN'; /* salt marsh a flat area, subject to periodic salt water inundation, dominated by grassy salt-tolerant plants */
    final public const NRWS  = 'NRWS';  /* narrows a navigable narrow part of a bay, strait, river, etc. */
    final public const OCN   = 'OCN';   /* ocean one of the major divisions of the vast expanse of salt water covering part of the earth */
    final public const OVF   = 'OVF';   /* overfalls an area of breaking waves caused by the meeting of currents or by waves moving against the current */
    final public const PND   = 'PND';   /* pond a small standing waterbody */
    final public const PNDI  = 'PNDI';  /* intermittent pond */
    final public const PNDN  = 'PNDN';  /* salt pond a small standing body of salt water often in a marsh or swamp, usually along a seacoast */
    final public const PNDNI = 'PNDNI'; /* intermittent salt pond(s) */
    final public const PNDS  = 'PNDS';  /* ponds small standing waterbodies */
    final public const PNDSF = 'PNDSF'; /* fishponds ponds or enclosures in which fish are kept or raised */
    final public const PNDSI = 'PNDSI'; /* intermittent ponds */
    final public const PNDSN = 'PNDSN'; /* salt ponds small standing bodies of salt water often in a marsh or swamp, usually along a seacoast */
    final public const POOL  = 'POOL';  /* pool(s) a small and comparatively still, deep part of a larger body of water such as a stream or harbor; or a small body of standing water */
    final public const POOLI = 'POOLI'; /* intermittent pool */
    final public const RCH   = 'RCH';   /* reach a straight section of a navigable stream or channel between two bends */
    final public const RDGG  = 'RDGG';  /* icecap ridge a linear elevation on an icecap */
    final public const RDST  = 'RDST';  /* roadstead an open anchorage affording less protection than a harbor */
    final public const RF    = 'RF';    /* reef(s) a surface-navigation hazard composed of consolidated material */
    final public const RFC   = 'RFC';   /* coral reef(s) a surface-navigation hazard composed of coral */
    final public const RFX   = 'RFX';   /* section of reef */
    final public const RPDS  = 'RPDS';  /* rapids a turbulent section of a stream associated with a steep, irregular stream bed */
    final public const RSV   = 'RSV';   /* reservoir(s) an artificial pond or lake */
    final public const RSVI  = 'RSVI';  /* intermittent reservoir */
    final public const RSVT  = 'RSVT';  /* water tank a contained pool or tank of water at, below, or above ground level */
    final public const RVN   = 'RVN';   /* ravine(s) a small, narrow, deep, steep-sided stream channel, smaller than a gorge */
    final public const SBKH  = 'SBKH';  /* sabkha(s) a salt flat or salt encrusted plain subject to periodic inundation from flooding or high tides */
    final public const SD    = 'SD';    /* sound a long arm of the sea forming a channel between the mainland and an island or islands; or connecting two larger bodies of water */
    final public const SEA   = 'SEA';   /* sea a large body of salt water more or less confined by continuous land or chains of islands forming a subdivision of an ocean */
    final public const SHOL  = 'SHOL';  /* shoal(s) a surface-navigation hazard composed of unconsolidated material */
    final public const SILL  = 'SILL';  /* sill the low part of an underwater gap or saddle separating basins, including a similar feature at the mouth of a fjord */
    final public const SPNG  = 'SPNG';  /* spring(s) a place where ground water flows naturally out of the ground */
    final public const SPNS  = 'SPNS';  /* sulphur spring(s) a place where sulphur ground water flows naturally out of the ground */
    final public const SPNT  = 'SPNT';  /* hot spring(s) a place where hot ground water flows naturally out of the ground */
    final public const STM   = 'STM';   /* stream a body of running water moving to a lower level in a channel on land */
    final public const STMA  = 'STMA';  /* anabranch a diverging branch flowing out of a main stream and rejoining it downstream */
    final public const STMB  = 'STMB';  /* stream bend a conspicuously curved or bent segment of a stream */
    final public const STMC  = 'STMC';  /* canalized stream a stream that has been substantially ditched, diked, or straightened */
    final public const STMD  = 'STMD';  /* distributary(-ies) a branch which flows away from the main stream, as in a delta or irrigation canal */
    final public const STMH  = 'STMH';  /* headwaters the source and upper part of a stream, including the upper drainage basin */
    final public const STMI  = 'STMI';  /* intermittent stream */
    final public const STMIX = 'STMIX'; /* section of intermittent stream */
    final public const STMM  = 'STMM';  /* stream mouth(s) a place where a stream discharges into a lagoon, lake, or the sea */
    final public const STMQ  = 'STMQ';  /* abandoned watercourse a former stream or distributary no longer carrying flowing water, but still evident due to lakes, wetland, topographic or vegetation patterns */
    final public const STMS  = 'STMS';  /* streams bodies of running water moving to a lower level in a channel on land */
    final public const STMSB = 'STMSB'; /* lost river a surface stream that disappears into an underground channel, or dries up in an arid area */
    final public const STMX  = 'STMX';  /* section of stream */
    final public const STRT  = 'STRT';  /* strait a relatively narrow waterway, usually narrower and less extensive than a sound, connecting two larger bodies of water */
    final public const SWMP  = 'SWMP';  /* swamp a wetland dominated by tree vegetation */
    final public const SYSI  = 'SYSI';  /* irrigation system a network of ditches and one or more of the following elements: water supply, reservoir, canal, pump, well, drain, etc. */
    final public const TNLC  = 'TNLC';  /* canal tunnel a tunnel through which a canal passes */
    final public const WAD   = 'WAD';   /* wadi a valley or ravine, bounded by relatively steep banks, which in the rainy season becomes a watercourse; found primarily in North Africa and the Middle East */
    final public const WADB  = 'WADB';  /* wadi bend a conspicuously curved or bent segment of a wadi */
    final public const WADJ  = 'WADJ';  /* wadi junction a place where two or more wadies join */
    final public const WADM  = 'WADM';  /* wadi mouth the lower terminus of a wadi where it widens into an adjoining floodplain, depression, or waterbody */
    final public const WADS  = 'WADS';  /* wadies valleys or ravines, bounded by relatively steep banks, which in the rainy season become watercourses; found primarily in North Africa and the Middle East */
    final public const WADX  = 'WADX';  /* section of wadi */
    final public const WHRL  = 'WHRL';  /* whirlpool a turbulent, rotating movement of water in a stream */
    final public const WLL   = 'WLL';   /* well a cylindrical hole, pit, or tunnel drilled or dug down to a depth from which water, oil, or gas can be pumped or brought to the surface */
    final public const WLLQ  = 'WLLQ';  /* abandoned well */
    final public const WLLS  = 'WLLS';  /* wells cylindrical holes, pits, or tunnels drilled or dug down to a depth from which water, oil, or gas can be pumped or brought to the surface */
    final public const WTLD  = 'WTLD';  /* wetland an area subject to inundation, usually characterized by bog, marsh, or swamp vegetation */
    final public const WTLDI = 'WTLDI'; /* intermittent wetland */
    final public const WTRC  = 'WTRC';  /* watercourse a natural, well-defined channel produced by flowing water, or an artificial channel designed to carry flowing water */
    final public const WTRH  = 'WTRH';  /* waterhole(s) a natural hole, hollow, or small depression that contains water, used by man and animals, especially in arid areas */

    /* L codes → Parks, Areas, etc. */
    final public const AGRC  = 'AGRC';  /* agricultural colony a tract of land set aside for agricultural settlement */
    final public const AMUS  = 'AMUS';  /* amusement parks are theme parks, adventure parks offering entertainment, similar to funfairs but with a fix location */
    final public const AREA  = 'AREA';  /* area a tract of land without homogeneous character or boundaries */
    final public const BSND  = 'BSND';  /* drainage basin an area drained by a stream */
    final public const BSNP  = 'BSNP';  /* petroleum basin an area underlain by an oil-rich structural basin */
    final public const BTL   = 'BTL';   /* battlefield a site of a land battle of historical importance */
    final public const CLG   = 'CLG';   /* clearing an area in a forest with trees removed */
    final public const CMN   = 'CMN';   /* common a park or pasture for community use */
    final public const CNS   = 'CNS';   /* concession area a lease of land by a government for economic development, e.g., mining, forestry */
    final public const COLF  = 'COLF';  /* coalfield a region in which coal deposits of possible economic value occur */
    final public const CONT  = 'CONT';  /* continent continent: Europe, Africa, Asia, North America, South America, Oceania, Antarctica */
    final public const CST   = 'CST';   /* coast a zone of variable width straddling the shoreline */
    final public const CTRB  = 'CTRB';  /* business center a place where a number of businesses are located */
    final public const DEVH  = 'DEVH';  /* housing development a tract of land on which many houses of similar design are built according to a development plan */
    final public const FLD   = 'FLD';   /* field(s) an open as opposed to wooded area */
    final public const FLDI  = 'FLDI';  /* irrigated field(s) a tract of level or terraced land which is irrigated */
    final public const GASF  = 'GASF';  /* gasfield an area containing a subterranean store of natural gas of economic value */
    final public const GRAZ  = 'GRAZ';  /* grazing area an area of grasses and shrubs used for grazing */
    final public const GVL   = 'GVL';   /* gravel area an area covered with gravel */
    final public const INDS  = 'INDS';  /* industrial area an area characterized by industrial activity */
    final public const LAND  = 'LAND';  /* arctic land a tract of land in the Arctic */
    final public const LCTY  = 'LCTY';  /* locality a minor area or place of unspecified or mixed character and indefinite boundaries */
    final public const MILB  = 'MILB';  /* military base a place used by an army or other armed service for storing arms and supplies, and for accommodating and training troops, a base from which operations can be initiated */
    final public const MNA   = 'MNA';   /* mining area an area of mine sites where minerals and ores are extracted */
    final public const MVA   = 'MVA';   /* maneuver area a tract of land where military field exercises are carried out */
    final public const NVB   = 'NVB';   /* naval base an area used to store supplies, provide barracks for troops and naval personnel, a port for naval vessels, and from which operations are initiated */
    final public const OAS   = 'OAS';   /* oasis(-es) an area in a desert made productive by the availability of water */
    final public const OILF  = 'OILF';  /* oilfield an area containing a subterranean store of petroleum of economic value */
    final public const PEAT  = 'PEAT';  /* peat cutting area an area where peat is harvested */
    final public const PRK   = 'PRK';   /* park an area, often of forested land, maintained as a place of beauty, or for recreation */
    final public const PRT   = 'PRT';   /* port a place provided with terminal and transfer facilities for loading and discharging waterborne cargo or passengers, usually located in a harbor */
    final public const QCKS  = 'QCKS';  /* quicksand an area where loose sand with water moving through it may become unstable when heavy objects are placed at the surface, causing them to sink */
    final public const RES   = 'RES';   /* reserve a tract of public land reserved for future use or restricted as to use */
    final public const RESA  = 'RESA';  /* agricultural reserve a tract of land reserved for agricultural reclamation and/or development */
    final public const RESF  = 'RESF';  /* forest reserve a forested area set aside for preservation or controlled use */
    final public const RESH  = 'RESH';  /* hunting reserve a tract of land used primarily for hunting */
    final public const RESN  = 'RESN';  /* nature reserve an area reserved for the maintenance of a natural habitat */
    final public const RESP  = 'RESP';  /* palm tree reserve an area of palm trees where use is controlled */
    final public const RESV  = 'RESV';  /* reservation a tract of land set aside for aboriginal, tribal, or native populations */
    final public const RESW  = 'RESW';  /* wildlife reserve a tract of public land reserved for the preservation of wildlife */
    final public const RGN   = 'RGN';   /* region an area distinguished by one or more observable physical or cultural characteristics */
    final public const RGNE  = 'RGNE';  /* economic region a region of a country established for economic development or for statistical purposes */
    final public const RGNH  = 'RGNH';  /* historical region a former historic area distinguished by one or more observable physical or cultural characteristics */
    final public const RGNL  = 'RGNL';  /* lake region a tract of land distinguished by numerous lakes */
    final public const RNGA  = 'RNGA';  /* artillery range a tract of land used for artillery firing practice */
    final public const SALT  = 'SALT';  /* salt area a shallow basin or flat where salt accumulates after periodic inundation */
    final public const SNOW  = 'SNOW';  /* snowfield an area of permanent snow and ice forming the accumulation area of a glacier */
    final public const TRB   = 'TRB';   /* tribal area a tract of land used by nomadic or other tribes */

    /* P codes → city, village, ... */
    final public const PPL   = 'PPL';   /* populated place; a city, town, village, or other agglomeration of buildings where people live and work */
    final public const PPLA  = 'PPLA';  /* seat of a first-order administrative division; seat of a first-order administrative division (PPLC takes precedence over PPLA) */
    final public const PPLA2 = 'PPLA2'; /* seat of a second-order administrative division */
    final public const PPLA3 = 'PPLA3'; /* seat of a third-order administrative division */
    final public const PPLA4 = 'PPLA4'; /* seat of a fourth-order administrative division */
    final public const PPLA5 = 'PPLA5'; /* seat of a fifth-order administrative division */
    final public const PPLC  = 'PPLC';  /* PPLC; capital of a political entity */
    final public const PPLCH = 'PPLCH'; /* historical capital of a political entity; a former capital of a political entity */
    final public const PPLF  = 'PPLF';  /* farm village; a populated place where the population is largely engaged in agricultural activities */
    final public const PPLG  = 'PPLG';  /* seat of government of a political entity */
    final public const PPLH  = 'PPLH';  /* historical populated place; a populated place that no longer exists */
    final public const PPLL  = 'PPLL';  /* populated locality; an area similar to a locality but with a small group of dwellings or other buildings */
    final public const PPLQ  = 'PPLQ';  /* abandoned populated place */
    final public const PPLR  = 'PPLR';  /* religious populated place; a populated place whose population is largely engaged in religious occupations */
    final public const PPLS  = 'PPLS';  /* populated places; cities, towns, villages, or other agglomerations of buildings where people live and work */
    final public const PPLW  = 'PPLW';  /* destroyed populated place; a village, town or city destroyed by a natural disaster, or by war */
    final public const PPLX  = 'PPLX';  /* section of populated place */
    final public const STLMT = 'STLMT'; /* israeli settlement */

    /* R codes → roads, railroads, etc. */
    final public const CSWY  = 'CSWY';  /* causeway a raised roadway across wet ground or shallow water */
    final public const OILP  = 'OILP';  /* oil pipeline a pipeline used for transporting oil */
    final public const PRMN  = 'PRMN';  /* promenade a place for public walking, usually along a beach front */
    final public const PTGE  = 'PTGE';  /* portage a place where boats, goods, etc., are carried overland between navigable waters */
    final public const RD    = 'RD';    /* road an open way with improved surface for transportation of animals, people and vehicles */
    final public const RDA   = 'RDA';   /* ancient road the remains of a road used by ancient cultures */
    final public const RDB   = 'RDB';   /* road bend a conspicuously curved or bent section of a road */
    final public const RDCUT = 'RDCUT'; /* road cut an excavation cut through a hill or ridge for a road */
    final public const RDJCT = 'RDJCT'; /* road junction a place where two or more roads join */
    final public const RJCT  = 'RJCT';  /* railroad junction a place where two or more railroad tracks join */
    final public const RR    = 'RR';    /* railroad a permanent twin steel-rail track on which freight and passenger cars move long distances */
    final public const RRQ   = 'RRQ';   /* abandoned railroad  */
    final public const RTE   = 'RTE';   /* caravan route the route taken by caravans */
    final public const RYD   = 'RYD';   /* railroad yard a system of tracks used for the making up of trains, and switching and storing freight cars */
    final public const ST    = 'ST';    /* street a paved urban thoroughfare */
    final public const STKR  = 'STKR';  /* stock route a route taken by livestock herds */
    final public const TNL   = 'TNL';   /* tunnel a subterranean passageway for transportation */
    final public const TNLN  = 'TNLN';  /* natural tunnel a cave that is open at both ends */
    final public const TNLRD = 'TNLRD'; /* road tunnel a tunnel through which a road passes */
    final public const TNLRR = 'TNLRR'; /* railroad tunnel a tunnel through which a railroad passes */
    final public const TNLS  = 'TNLS';  /* tunnels subterranean passageways for transportation */
    final public const TRL   = 'TRL';   /* trail a path, track, or route used by pedestrians, animals, or off-road vehicles */

    /* S codes → spots, buildings, farms, etc. */
    final public const ADMF  = 'ADMF';  /* administrative facility a government building */
    final public const AGRF  = 'AGRF';  /* agricultural facility a building and/or tract of land used for improving agriculture */
    final public const AIRB  = 'AIRB';  /* airbase an area used to store supplies, provide barracks for air force personnel, hangars and runways for aircraft, and from which operations are initiated */
    final public const AIRF  = 'AIRF';  /* airfield a place on land where aircraft land and take off; no facilities provided for the commercial handling of passengers and cargo */
    final public const AIRH  = 'AIRH';  /* heliport a place where helicopters land and take off */
    final public const AIRP  = 'AIRP';  /* airport a place where aircraft regularly land and take off, with runways, navigational aids, and major facilities for the commercial handling of passengers and cargo */
    final public const AIRQ  = 'AIRQ';  /* abandoned airfield */
    final public const AIRT  = 'AIRT';  /* terminal airport facilities for the handling of freight and passengers */
    final public const AMTH  = 'AMTH';  /* amphitheater an oval or circular structure with rising tiers of seats about a stage or open space */
    final public const ANS   = 'ANS';   /* archaeological/prehistoric site a place where archeological remains, old structures, or cultural artifacts are located */
    final public const AQC   = 'AQC';   /* aquaculture facility facility or area for the cultivation of aquatic animals and plants, especially fish, shellfish, and seaweed, in natural or controlled marine or freshwater environments; underwater agriculture */
    final public const ARCH  = 'ARCH';  /* arch a natural or man-made structure in the form of an arch */
    final public const ARCHV = 'ARCHV'; /* archive a place or institution where documents are preserved */
    final public const ART   = 'ART';   /* piece of art a piece of art, like a sculpture, painting. In contrast to monument (MNMT) it is not commemorative. */
    final public const ASTR  = 'ASTR';  /* astronomical station a point on the earth whose position has been determined by observations of celestial bodies */
    final public const ASYL  = 'ASYL';  /* asylum a facility where the insane are cared for and protected */
    final public const ATHF  = 'ATHF';  /* athletic field a tract of land used for playing team sports, and athletic track and field events */
    final public const ATM   = 'ATM';   /* automatic teller machine An unattended electronic machine in a public place, connected to a data system and related equipment and activated by a bank customer to obtain cash withdrawals and other banking services. */
    final public const BANK  = 'BANK';  /* bank A business establishment in which money is kept for saving or commercial purposes or is invested, supplied for loans, or exchanged. */
    final public const BCN   = 'BCN';   /* beacon a fixed artificial navigation mark */
    final public const BDG   = 'BDG';   /* bridge a structure erected across an obstacle such as a stream, road, etc., in order to carry roads, railroads, and pedestrians across */
    final public const BDGQ  = 'BDGQ';  /* ruined bridge a destroyed or decayed bridge which is no longer functional */
    final public const BLDA  = 'BLDA';  /* apartment building a building containing several individual apartments */
    final public const BLDG  = 'BLDG';  /* building(s) a structure built for permanent use, as a house, factory, etc. */
    final public const BLDO  = 'BLDO';  /* office building commercial building where business and/or services are conducted */
    final public const BP    = 'BP';    /* boundary marker a fixture marking a point along a boundary */
    final public const BRKS  = 'BRKS';  /* barracks a building for lodging military personnel */
    final public const BRKW  = 'BRKW';  /* breakwater a structure erected to break the force of waves at the entrance to a harbor or port */
    final public const BSTN  = 'BSTN';  /* baling station a facility for baling agricultural products */
    final public const BTYD  = 'BTYD';  /* boatyard a waterside facility for servicing, repairing, and building small vessels */
    final public const BUR   = 'BUR';   /* burial cave(s) a cave used for human burials */
    final public const BUSTN = 'BUSTN'; /* bus station a facility comprising ticket office, platforms, etc. for loading and unloading passengers */
    final public const BUSTP = 'BUSTP'; /* bus stop a place lacking station facilities */
    final public const CARN  = 'CARN';  /* cairn a heap of stones erected as a landmark or for other purposes */
    final public const CAVE  = 'CAVE';  /* cave(s) an underground passageway or chamber, or cavity on the side of a cliff */
    final public const CH    = 'CH';    /* church a building for public Christian worship */
    final public const CMP   = 'CMP';   /* camp(s) a site occupied by tents, huts, or other shelters for temporary use */final public const CMPL = 'CMPL'; /* logging camp a camp used by loggers */
    final public const CMPLA = 'CMPLA'; /* labor camp a camp used by migrant or temporary laborers */
    final public const CMPMN = 'CMPMN'; /* mining camp a camp used by miners */
    final public const CMPO  = 'CMPO';  /* oil camp a camp used by oilfield workers */
    final public const CMPQ  = 'CMPQ';  /* abandoned camp  */
    final public const CMPRF = 'CMPRF'; /* refugee camp a camp used by refugees */
    final public const CMTY  = 'CMTY';  /* cemetery a burial place or ground */
    final public const COMC  = 'COMC';  /* communication center a facility, including buildings, antennae, towers and electronic equipment for receiving and transmitting information */
    final public const CRRL  = 'CRRL';  /* corral(s) a pen or enclosure for confining or capturing animals */
    final public const CSNO  = 'CSNO';  /* casino a building used for entertainment, especially gambling */
    final public const CSTL  = 'CSTL';  /* castle a large fortified building or set of buildings */
    final public const CSTM  = 'CSTM';  /* customs house a building in a port where customs and duties are paid, and where vessels are entered and cleared */
    final public const CTHSE = 'CTHSE'; /* courthouse a building in which courts of law are held */
    final public const CTRA  = 'CTRA';  /* atomic center a facility where atomic research is carried out */
    final public const CTRCM = 'CTRCM'; /* community center a facility for community recreation and other activities */
    final public const CTRF  = 'CTRF';  /* facility center a place where more than one facility is situated */
    final public const CTRM  = 'CTRM';  /* medical center a complex of health care buildings including two or more of the following: hospital, medical school, clinic, pharmacy, doctor's offices, etc. */
    final public const CTRR  = 'CTRR';  /* religious center a facility where more than one religious activity is carried out, e.g., retreat, school, monastery, worship */
    final public const CTRS  = 'CTRS';  /* space center a facility for launching, tracking, or controlling satellites and space vehicles */
    final public const CVNT  = 'CVNT';  /* convent a building where a community of nuns lives in seclusion */
    final public const DAM   = 'DAM';   /* dam a barrier constructed across a stream to impound water */
    final public const DAMQ  = 'DAMQ';  /* ruined dam a destroyed or decayed dam which is no longer functional */
    final public const DAMSB = 'DAMSB'; /* sub-surface dam a dam put down to bedrock in a sand river */
    final public const DARY  = 'DARY';  /* dairy a facility for the processing, sale and distribution of milk or milk products */
    final public const DCKD  = 'DCKD';  /* dry dock a dock providing support for a vessel, and means for removing the water so that the bottom of the vessel can be exposed */
    final public const DCKY  = 'DCKY';  /* dockyard a facility for servicing, building, or repairing ships */
    final public const DIKE  = 'DIKE';  /* dike an earth or stone embankment usually constructed for flood or stream control */
    final public const DIP   = 'DIP';   /* diplomatic facility office, residence, or facility of a foreign government, which may include an embassy, consulate, chancery, office of charge d'affaires, or other diplomatic, economic, military, or cultural mission */
    final public const DPOF  = 'DPOF';  /* fuel depot an area where fuel is stored */
    final public const EST   = 'EST';   /* estate(s) a large commercialized agricultural landholding with associated buildings and other facilities */
    final public const ESTO  = 'ESTO';  /* oil palm plantation an estate specializing in the cultivation of oil palm trees */
    final public const ESTR  = 'ESTR';  /* rubber plantation an estate which specializes in growing and tapping rubber trees */
    final public const ESTSG = 'ESTSG'; /* sugar plantation an estate that specializes in growing sugar cane */
    final public const ESTT  = 'ESTT';  /* tea plantation an estate which specializes in growing tea bushes */
    final public const ESTX  = 'ESTX';  /* section of estate  */
    final public const FCL   = 'FCL';   /* facility a building or buildings housing a center, institute, foundation, hospital, prison, mission, courthouse, etc. */
    final public const FNDY  = 'FNDY';  /* foundry a building or works where metal casting is carried out */
    final public const FRM   = 'FRM';   /* farm a tract of land with associated buildings devoted to agriculture */
    final public const FRMQ  = 'FRMQ';  /* abandoned farm  */
    final public const FRMS  = 'FRMS';  /* farms tracts of land with associated buildings devoted to agriculture */
    final public const FRMT  = 'FRMT';  /* farmstead the buildings and adjacent service areas of a farm */
    final public const FT    = 'FT';    /* fort a defensive structure or earthworks */
    final public const FY    = 'FY';    /* ferry a boat or other floating conveyance and terminal facilities regularly used to transport people and vehicles across a waterbody */
    final public const FYT   = 'FYT';   /* ferry terminal a place where ferries pick-up and discharge passengers, vehicles and or cargo */
    final public const GATE  = 'GATE';  /* gate a controlled access entrance or exit */
    final public const GDN   = 'GDN';   /* garden(s) an enclosure for displaying selected plant or animal life */
    final public const GHAT  = 'GHAT';  /* ghat a set of steps leading to a river, which are of religious significance, and at their base is usually a platform for bathing */
    final public const GHSE  = 'GHSE';  /* guest house a house used to provide lodging for paying guests */
    final public const GOSP  = 'GOSP';  /* gas-oil separator plant a facility for separating gas from oil */
    final public const GOVL  = 'GOVL';  /* local government office a facility housing local governmental offices, usually a city, town, or village hall */
    final public const GRVE  = 'GRVE';  /* grave a burial site */
    final public const HERM  = 'HERM';  /* hermitage a secluded residence, usually for religious sects */
    final public const HLT   = 'HLT';   /* halting place a place where caravans stop for rest */
    final public const HMSD  = 'HMSD';  /* homestead a residence, owner's or manager's, on a sheep or cattle station, woolshed, outcamp, or Aboriginal outstation, specific to Australia and New Zealand */
    final public const HSE   = 'HSE';   /* house(s) a building used as a human habitation */
    final public const HSEC  = 'HSEC';  /* country house a large house, mansion, or chateau, on a large estate */
    final public const HSP   = 'HSP';   /* hospital a building in which sick or injured, especially those confined to bed, are medically treated */
    final public const HSPC  = 'HSPC';  /* clinic a medical facility associated with a hospital for outpatients */
    final public const HSPD  = 'HSPD';  /* dispensary a building where medical or dental aid is dispensed */
    final public const HSPL  = 'HSPL';  /* leprosarium an asylum or hospital for lepers */
    final public const HSTS  = 'HSTS';  /* historical site a place of historical importance */
    final public const HTL   = 'HTL';   /* hotel a building providing lodging and/or meals for the public */
    final public const HUT   = 'HUT';   /* hut a small primitive house */
    final public const HUTS  = 'HUTS';  /* huts small primitive houses */
    final public const INSM  = 'INSM';  /* military installation a facility for use of and control by armed forces */
    final public const ITTR  = 'ITTR';  /* research institute a facility where research is carried out */
    final public const JTY   = 'JTY';   /* jetty a structure built out into the water at a river mouth or harbor entrance to regulate currents and silting */
    final public const LDNG  = 'LDNG';  /* landing a place where boats receive or discharge passengers and freight, but lacking most port facilities */
    final public const LEPC  = 'LEPC';  /* leper colony a settled area inhabited by lepers in relative isolation */
    final public const LIBR  = 'LIBR';  /* library A place in which information resources such as books are kept for reading, reference, or lending. */
    final public const LNDF  = 'LNDF';  /* landfill a place for trash and garbage disposal in which the waste is buried between layers of earth to build up low-lying land */
    final public const LOCK  = 'LOCK';  /* lock(s) a basin in a waterway with gates at each end by means of which vessels are passed from one water level to another */
    final public const LTHSE = 'LTHSE'; /* lighthouse a distinctive structure exhibiting a major navigation light */
    final public const MALL  = 'MALL';  /* mall A large, often enclosed shopping complex containing various stores, businesses, and restaurants usually accessible by common passageways. */
    final public const MAR   = 'MAR';   /* marina a harbor facility for small boats, yachts, etc. */
    final public const MFG   = 'MFG';   /* factory one or more buildings where goods are manufactured, processed or fabricated */
    final public const MFGB  = 'MFGB';  /* brewery one or more buildings where beer is brewed */
    final public const MFGC  = 'MFGC';  /* cannery a building where food items are canned */
    final public const MFGCU = 'MFGCU'; /* copper works a facility for processing copper ore */
    final public const MFGLM = 'MFGLM'; /* limekiln a furnace in which limestone is reduced to lime */
    final public const MFGM  = 'MFGM';  /* munitions plant a factory where ammunition is made */
    final public const MFGPH = 'MFGPH'; /* phosphate works a facility for producing fertilizer */
    final public const MFGQ  = 'MFGQ';  /* abandoned factory  */
    final public const MFGSG = 'MFGSG'; /* sugar refinery a facility for converting raw sugar into refined sugar */
    final public const MKT   = 'MKT';   /* market a place where goods are bought and sold at regular intervals */
    final public const ML    = 'ML';    /* mill(s) a building housing machines for transforming, shaping, finishing, grinding, or extracting products */
    final public const MLM   = 'MLM';   /* ore treatment plant a facility for improving the metal content of ore by concentration */
    final public const MLO   = 'MLO';   /* olive oil mill a mill where oil is extracted from olives */
    final public const MLSG  = 'MLSG';  /* sugar mill a facility where sugar cane is processed into raw sugar */
    final public const MLSGQ = 'MLSGQ'; /* former sugar mill a sugar mill no longer used as a sugar mill */
    final public const MLSW  = 'MLSW';  /* sawmill a mill where logs or lumber are sawn to specified shapes and sizes */
    final public const MLWND = 'MLWND'; /* windmill a mill or water pump powered by wind */
    final public const MLWTR = 'MLWTR'; /* water mill a mill powered by running water */
    final public const MN    = 'MN';    /* mine(s) a site where mineral ores are extracted from the ground by excavating surface pits and subterranean passages */
    final public const MNAU  = 'MNAU';  /* gold mine(s) a mine where gold ore, or alluvial gold is extracted */
    final public const MNC   = 'MNC';   /* coal mine(s) a mine where coal is extracted */
    final public const MNCR  = 'MNCR';  /* chrome mine(s) a mine where chrome ore is extracted */
    final public const MNCU  = 'MNCU';  /* copper mine(s) a mine where copper ore is extracted */
    final public const MNFE  = 'MNFE';  /* iron mine(s) a mine where iron ore is extracted */
    final public const MNMT  = 'MNMT';  /* monument a commemorative structure or statue */
    final public const MNN   = 'MNN';   /* salt mine(s) a mine from which salt is extracted */
    final public const MNQ   = 'MNQ';   /* abandoned mine  */
    final public const MNQR  = 'MNQR';  /* quarry(-ies) a surface mine where building stone or gravel and sand, etc. are extracted */
    final public const MOLE  = 'MOLE';  /* mole a massive structure of masonry or large stones serving as a pier or breakwater */
    final public const MSQE  = 'MSQE';  /* mosque a building for public Islamic worship */
    final public const MSSN  = 'MSSN';  /* mission a place characterized by dwellings, school, church, hospital and other facilities operated by a religious group for the purpose of providing charitable services and to propagate religion */
    final public const MSSNQ = 'MSSNQ'; /* abandoned mission  */
    final public const MSTY  = 'MSTY';  /* monastery a building and grounds where a community of monks lives in seclusion */
    final public const MTRO  = 'MTRO';  /* metro station metro station (Underground, Tube, or Metro) */
    final public const MUS   = 'MUS';   /* museum a building where objects of permanent interest in one or more of the arts and sciences are preserved and exhibited */
    final public const NOV   = 'NOV';   /* novitiate a religious house or school where novices are trained */
    final public const NSY   = 'NSY';   /* nursery(-ies) a place where plants are propagated for transplanting or grafting */
    final public const OBPT  = 'OBPT';  /* observation point a wildlife or scenic observation point */
    final public const OBS   = 'OBS';   /* observatory a facility equipped for observation of atmospheric or space phenomena */
    final public const OBSR  = 'OBSR';  /* radio observatory a facility equipped with an array of antennae for receiving radio waves from space */
    final public const OILJ  = 'OILJ';  /* oil pipeline junction a section of an oil pipeline where two or more pipes join together */
    final public const OILQ  = 'OILQ';  /* abandoned oil well  */
    final public const OILR  = 'OILR';  /* oil refinery a facility for converting crude oil into refined petroleum products */
    final public const OILT  = 'OILT';  /* tank farm a tract of land occupied by large, cylindrical, metal tanks in which oil or liquid petrochemicals are stored */
    final public const OILW  = 'OILW';  /* oil well a well from which oil may be pumped */
    final public const OPRA  = 'OPRA';  /* opera house A theater designed chiefly for the performance of operas. */
    final public const PAL   = 'PAL';   /* palace a large stately house, often a royal or presidential residence */
    final public const PGDA  = 'PGDA';  /* pagoda a tower-like storied structure, usually a Buddhist shrine */
    final public const PIER  = 'PIER';  /* pier a structure built out into navigable water on piles providing berthing for ships and recreation */
    final public const PKLT  = 'PKLT';  /* parking lot an area used for parking vehicles */
    final public const PMPO  = 'PMPO';  /* oil pumping station a facility for pumping oil through a pipeline */
    final public const PMPW  = 'PMPW';  /* water pumping station a facility for pumping water from a major well or through a pipeline */
    final public const PO    = 'PO';    /* post office a public building in which mail is received, sorted and distributed */
    final public const PP    = 'PP';    /* police post a building in which police are stationed */
    final public const PPQ   = 'PPQ';   /* abandoned police post  */
    final public const PRKGT = 'PRKGT'; /* park gate a controlled access to a park */
    final public const PRKHQ = 'PRKHQ'; /* park headquarters a park administrative facility */
    final public const PRN   = 'PRN';   /* prison a facility for confining prisoners */
    final public const PRNJ  = 'PRNJ';  /* reformatory a facility for confining, training, and reforming young law offenders */
    final public const PRNQ  = 'PRNQ';  /* abandoned prison  */
    final public const PS    = 'PS';    /* power station a facility for generating electric power */
    final public const PSH   = 'PSH';   /* hydroelectric power station a building where electricity is generated from water power */
    final public const PSN   = 'PSN';   /* nuclear power station nuclear power station */
    final public const PSTB  = 'PSTB';  /* border post a post or station at an international boundary for the regulation of movement of people and goods */
    final public const PSTC  = 'PSTC';  /* customs post a building at an international boundary where customs and duties are paid on goods */
    final public const PSTP  = 'PSTP';  /* patrol post a post from which patrols are sent out */
    final public const PYR   = 'PYR';   /* pyramid an ancient massive structure of square ground plan with four triangular faces meeting at a point and used for enclosing tombs */
    final public const PYRS  = 'PYRS';  /* pyramids ancient massive structures of square ground plan with four triangular faces meeting at a point and used for enclosing tombs */
    final public const QUAY  = 'QUAY';  /* quay a structure of solid construction along a shore or bank which provides berthing for ships and which generally provides cargo handling facilities */
    final public const RDCR  = 'RDCR';  /* traffic circle a road junction formed around a central circle about which traffic moves in one direction only */
    final public const RDIN  = 'RDIN';  /* intersection a junction of two or more highways by a system of separate levels that permit traffic to pass from one to another without the crossing of traffic streams */
    final public const RECG  = 'RECG';  /* golf course a recreation field where golf is played */
    final public const RECR  = 'RECR';  /* racetrack a track where races are held */
    final public const REST  = 'REST';  /* restaurant A place where meals are served to the public */
    final public const RET   = 'RET';   /* store a building where goods and/or services are offered for sale */
    final public const RHSE  = 'RHSE';  /* resthouse a structure maintained for the rest and shelter of travelers */
    final public const RKRY  = 'RKRY';  /* rookery a breeding place of a colony of birds or seals */
    final public const RLG   = 'RLG';   /* religious site an ancient site of significant religious importance */
    final public const RLGR  = 'RLGR';  /* retreat a place of temporary seclusion, especially for religious groups */
    final public const RNCH  = 'RNCH';  /* ranch(es) a large farm specializing in extensive grazing of livestock */
    final public const RSD   = 'RSD';   /* railroad siding a short track parallel to and joining the main track */
    final public const RSGNL = 'RSGNL'; /* railroad signal a signal at the entrance of a particular section of track governing the movement of trains */
    final public const RSRT  = 'RSRT';  /* resort a specialized facility for vacation, health, or participation sports activities */
    final public const RSTN  = 'RSTN';  /* railroad station a facility comprising ticket office, platforms, etc. for loading and unloading train passengers and freight */
    final public const RSTNQ = 'RSTNQ'; /* abandoned railroad station  */
    final public const RSTP  = 'RSTP';  /* railroad stop a place lacking station facilities where trains stop to pick up and unload passengers and freight */
    final public const RSTPQ = 'RSTPQ'; /* abandoned railroad stop  */
    final public const RUIN  = 'RUIN';  /* ruin(s) a destroyed or decayed structure which is no longer functional */
    final public const SCH   = 'SCH';   /* school building(s) where instruction in one or more branches of knowledge takes place */
    final public const SCHA  = 'SCHA';  /* agricultural school a school with a curriculum focused on agriculture */
    final public const SCHC  = 'SCHC';  /* college the grounds and buildings of an institution of higher learning */
    final public const SCHL  = 'SCHL';  /* language school Language Schools & Institutions */
    final public const SCHM  = 'SCHM';  /* military school a school at which military science forms the core of the curriculum */
    final public const SCHN  = 'SCHN';  /* maritime school a school at which maritime sciences form the core of the curriculum */
    final public const SCHT  = 'SCHT';  /* technical school post-secondary school with a specifically technical or vocational curriculum */
    final public const SECP  = 'SECP';  /* State Exam Prep Centre state exam preparation centres */
    final public const SHPF  = 'SHPF';  /* sheepfold a fence or wall enclosure for sheep and other small herd animals */
    final public const SHRN  = 'SHRN';  /* shrine a structure or place memorializing a person or religious concept */
    final public const SHSE  = 'SHSE';  /* storehouse a building for storing goods, especially provisions */
    final public const SLCE  = 'SLCE';  /* sluice a conduit or passage for carrying off surplus water from a waterbody, usually regulated by means of a sluice gate */
    final public const SNTR  = 'SNTR';  /* sanatorium a facility where victims of physical or mental disorders are treated */
    final public const SPA   = 'SPA';   /* spa a resort area usually developed around a medicinal spring */
    final public const SPLY  = 'SPLY';  /* spillway a passage or outlet through which surplus water flows over, around or through a dam */
    final public const SQR   = 'SQR';   /* square a broad, open, public area near the center of a town or city */
    final public const STBL  = 'STBL';  /* stable a building for the shelter and feeding of farm animals, especially horses */
    final public const STDM  = 'STDM';  /* stadium a structure with an enclosure for athletic games with tiers of seats for spectators */
    final public const STNB  = 'STNB';  /* scientific research base a scientific facility used as a base from which research is carried out or monitored */
    final public const STNC  = 'STNC';  /* coast guard station a facility from which the coast is guarded by armed vessels */
    final public const STNE  = 'STNE';  /* experiment station a facility for carrying out experiments */
    final public const STNF  = 'STNF';  /* forest station a collection of buildings and facilities for carrying out forest management */
    final public const STNI  = 'STNI';  /* inspection station a station at which vehicles, goods, and people are inspected */
    final public const STNM  = 'STNM';  /* meteorological station a station at which weather elements are recorded */
    final public const STNR  = 'STNR';  /* radio station a facility for producing and transmitting information by radio waves */
    final public const STNS  = 'STNS';  /* satellite station a facility for tracking and communicating with orbiting satellites */
    final public const STNW  = 'STNW';  /* whaling station a facility for butchering whales and processing train oil */
    final public const STPS  = 'STPS';  /* steps stones or slabs placed for ease in ascending or descending a steep slope */
    final public const SWT   = 'SWT';   /* sewage treatment plant facility for the processing of sewage and/or wastewater */
    final public const SYG   = 'SYG';   /* synagogue a place for Jewish worship and religious instruction */
    final public const THTR  = 'THTR';  /* theater A building, room, or outdoor structure for the presentation of plays, films, or other dramatic performances */
    final public const TMB   = 'TMB';   /* tomb(s) a structure for interring bodies */
    final public const TMPL  = 'TMPL';  /* temple(s) an edifice dedicated to religious worship */
    final public const TNKD  = 'TNKD';  /* cattle dipping tank a small artificial pond used for immersing cattle in chemically treated water for disease control */
    final public const TOLL  = 'TOLL';  /* toll gate/barrier highway toll collection station */
    final public const TOWR  = 'TOWR';  /* tower a high conspicuous structure, typically much higher than its diameter */
    final public const TRAM  = 'TRAM';  /* tram rail vehicle along urban streets (also known as streetcar or trolley) */
    final public const TRANT = 'TRANT'; /* transit terminal facilities for the handling of vehicular freight and passengers */
    final public const TRIG  = 'TRIG';  /* triangulation station a point on the earth whose position has been determined by triangulation */
    final public const TRMO  = 'TRMO';  /* oil pipeline terminal a tank farm or loading facility at the end of an oil pipeline */
    final public const TWO   = 'TWO';   /* temp work office Temporary Work Offices */
    final public const UNIP  = 'UNIP';  /* university prep school University Preparation Schools & Institutions */
    final public const UNIV  = 'UNIV';  /* university An institution for higher learning with teaching and research facilities constituting a graduate school and professional schools that award master's degrees and doctorates and an undergraduate division that awards bachelor's degrees. */
    final public const USGE  = 'USGE';  /* united states government establishment a facility operated by the United States Government in Panama */
    final public const VETF  = 'VETF';  /* veterinary facility a building or camp at which veterinary services are available */
    final public const WALL  = 'WALL';  /* wall a thick masonry structure, usually enclosing a field or building, or forming the side of a structure */
    final public const WALLA = 'WALLA'; /* ancient wall the remains of a linear defensive stone structure */
    final public const WEIR  = 'WEIR';  /* weir(s) a small dam in a stream, designed to raise the water level or to divert stream flow through a desired channel */
    final public const WHRF  = 'WHRF';  /* wharf(-ves) a structure of open rather than solid construction along a shore or a bank which provides berthing for ships and cargo-handling facilities */
    final public const WRCK  = 'WRCK';  /* wreck the site of the remains of a wrecked vessel */
    final public const WTRW  = 'WTRW';  /* waterworks a facility for supplying potable water through a water source and a system of pumps and filtration beds */
    final public const ZNF   = 'ZNF';   /* free trade zone an area, usually a section of a port, where goods may be received and shipped free of customs duty and of most customs regulations */
    final public const ZOO   = 'ZOO';   /* zoo a zoological garden or park where wild animals are kept for exhibition */

    /* T codes → mountains ,hills, rocks, etc. */
    final public const ASPH  = 'ASPH';  /* asphalt lake a small basin containing naturally occurring asphalt */
    final public const ATOL  = 'ATOL';  /* atoll(s) a ring-shaped coral reef which has closely spaced islands on it encircling a lagoon */
    final public const BAR   = 'BAR';   /* bar a shallow ridge or mound of coarse unconsolidated material in a stream channel, at the mouth of a stream, estuary, or lagoon and in the wave-break zone along coasts */
    final public const BCH   = 'BCH';   /* beach a shore zone of coarse unconsolidated sediment that extends from the low-water line to the highest reach of storm waves */
    final public const BCHS  = 'BCHS';  /* beaches a shore zone of coarse unconsolidated sediment that extends from the low-water line to the highest reach of storm waves */
    final public const BDLD  = 'BDLD';  /* badlands an area characterized by a maze of very closely spaced, deep, narrow, steep-sided ravines, and sharp crests and pinnacles */
    final public const BLDR  = 'BLDR';  /* boulder field a high altitude or high latitude bare, flat area covered with large angular rocks */
    final public const BLHL  = 'BLHL';  /* blowhole(s) a hole in coastal rock through which sea water is forced by a rising tide or waves and spurted through an outlet into the air */
    final public const BLOW  = 'BLOW';  /* blowout(s) a small depression in sandy terrain, caused by wind erosion */
    final public const BNCH  = 'BNCH';  /* bench a long, narrow bedrock platform bounded by steeper slopes above and below, usually overlooking a waterbody */
    final public const BUTE  = 'BUTE';  /* butte(s) a small, isolated, usually flat-topped hill with steep sides */
    final public const CAPE  = 'CAPE';  /* cape a land area, more prominent than a point, projecting into the sea and marking a notable change in coastal direction */
    final public const CFT   = 'CFT';   /* cleft(s) a deep narrow slot, notch, or groove in a coastal cliff */
    final public const CLDA  = 'CLDA';  /* caldera a depression measuring kilometers across formed by the collapse of a volcanic mountain */
    final public const CLF   = 'CLF';   /* cliff(s) a high, steep to perpendicular slope overlooking a waterbody or lower area */
    final public const CNYN  = 'CNYN';  /* canyon a deep, narrow valley with steep sides cutting into a plateau or mountainous area */
    final public const CONE  = 'CONE';  /* cone(s) a conical landform composed of mud or volcanic material */
    final public const CRDR  = 'CRDR';  /* corridor a strip or area of land having significance as an access way */
    final public const CRQ   = 'CRQ';   /* cirque a bowl-like hollow partially surrounded by cliffs or steep slopes at the head of a glaciated valley */
    final public const CRQS  = 'CRQS';  /* cirques bowl-like hollows partially surrounded by cliffs or steep slopes at the head of a glaciated valley */
    final public const CRTR  = 'CRTR';  /* crater(s) a generally circular saucer or bowl-shaped depression caused by volcanic or meteorite explosive action */
    final public const CUET  = 'CUET';  /* cuesta(s) an asymmetric ridge formed on tilted strata */
    final public const DLTA  = 'DLTA';  /* delta a flat plain formed by alluvial deposits at the mouth of a stream */
    final public const DPR   = 'DPR';   /* depression(s) a low area surrounded by higher land and usually characterized by interior drainage */
    final public const DSRT  = 'DSRT';  /* desert a large area with little or no vegetation due to extreme environmental conditions */
    final public const DUNE  = 'DUNE';  /* dune(s) a wave form, ridge or star shape feature composed of sand */
    final public const DVD   = 'DVD';   /* divide a line separating adjacent drainage basins */
    final public const ERG   = 'ERG';   /* sandy desert an extensive tract of shifting sand and sand dunes */
    final public const FAN   = 'FAN';   /* fan(s) a fan-shaped wedge of coarse alluvium with apex merging with a mountain stream bed and the fan spreading out at a low angle slope onto an adjacent plain */
    final public const FORD  = 'FORD';  /* ford a shallow part of a stream which can be crossed on foot or by land vehicle */
    final public const FSR   = 'FSR';   /* fissure a crack associated with volcanism */
    final public const GAP   = 'GAP';   /* gap a low place in a ridge, not used for transportation */
    final public const GRGE  = 'GRGE';  /* gorge(s) a short, narrow, steep-sided section of a stream valley */
    final public const HDLD  = 'HDLD';  /* headland a high projection of land extending into a large body of water beyond the line of the coast */
    final public const HLL   = 'HLL';   /* hill a rounded elevation of limited extent rising above the surrounding land with local relief of less than 300m */
    final public const HLLS  = 'HLLS';  /* hills rounded elevations of limited extent rising above the surrounding land with local relief of less than 300m */
    final public const HMCK  = 'HMCK';  /* hammock(s) a patch of ground, distinct from and slightly above the surrounding plain or wetland. Often occurs in groups */
    final public const HMDA  = 'HMDA';  /* rock desert a relatively sand-free, high bedrock plateau in a hot desert, with or without a gravel veneer */
    final public const INTF  = 'INTF';  /* interfluve a relatively undissected upland between adjacent stream valleys */
    final public const ISL   = 'ISL';   /* island a tract of land, smaller than a continent, surrounded by water at high water */
    final public const ISLET = 'ISLET'; /* islet small island, bigger than rock, smaller than island. */
    final public const ISLF  = 'ISLF';  /* artificial island an island created by landfill or diking and filling in a wetland, bay, or lagoon */
    final public const ISLM  = 'ISLM';  /* mangrove island a mangrove swamp surrounded by a waterbody */
    final public const ISLS  = 'ISLS';  /* islands tracts of land, smaller than a continent, surrounded by water at high water */
    final public const ISLT  = 'ISLT';  /* land-tied island a coastal island connected to the mainland by barrier beaches, levees or dikes */
    final public const ISLX  = 'ISLX';  /* section of island */
    final public const ISTH  = 'ISTH';  /* isthmus a narrow strip of land connecting two larger land masses and bordered by water */
    final public const KRST  = 'KRST';  /* karst area a distinctive landscape developed on soluble rock such as limestone characterized by sinkholes, caves, disappearing streams, and underground drainage */
    final public const LAVA  = 'LAVA';  /* lava area an area of solidified lava */
    final public const LEV   = 'LEV';   /* levee a natural low embankment bordering a distributary or meandering stream; often built up artificially to control floods */
    final public const MESA  = 'MESA';  /* mesa(s) a flat-topped, isolated elevation with steep slopes on all sides, less extensive than a plateau */
    final public const MND   = 'MND';   /* mound(s) a low, isolated, rounded hill */
    final public const MRN   = 'MRN';   /* moraine a mound, ridge, or other accumulation of glacial till */
    final public const MT    = 'MT';    /* mountain an elevation standing high above the surrounding area with small summit area, steep slopes and local relief of 300m or more */
    final public const MTS   = 'MTS';   /* mountains a mountain range or a group of mountains or high ridges */
    final public const NKM   = 'NKM';   /* meander neck a narrow strip of land between the two limbs of a meander loop at its narrowest point */
    final public const NTK   = 'NTK';   /* nunatak a rock or mountain peak protruding through glacial ice */
    final public const NTKS  = 'NTKS';  /* nunataks rocks or mountain peaks protruding through glacial ice */
    final public const PAN   = 'PAN';   /* pan a near-level shallow, natural depression or basin, usually containing an intermittent lake, pond, or pool */
    final public const PANS  = 'PANS';  /* pans a near-level shallow, natural depression or basin, usually containing an intermittent lake, pond, or pool */
    final public const PASS  = 'PASS';  /* pass a break in a mountain range or other high obstruction, used for transportation from one side to the other [See also gap] */
    final public const PEN   = 'PEN';   /* peninsula an elongate area of land projecting into a body of water and nearly surrounded by water */
    final public const PENX  = 'PENX';  /* section of peninsula */
    final public const PK    = 'PK';    /* peak a pointed elevation atop a mountain, ridge, or other hypsographic feature */
    final public const PKS   = 'PKS';   /* peaks pointed elevations atop a mountain, ridge, or other hypsographic features */
    final public const PLAT  = 'PLAT';  /* plateau an elevated plain with steep slopes on one or more sides, and often with incised streams */
    final public const PLATX = 'PLATX'; /* section of plateau */
    final public const PLDR  = 'PLDR';  /* polder an area reclaimed from the sea by diking and draining */
    final public const PLN   = 'PLN';   /* plain(s) an extensive area of comparatively level to gently undulating land, lacking surface irregularities, and usually adjacent to a higher area */
    final public const PLNX  = 'PLNX';  /* section of plain */
    final public const PROM  = 'PROM';  /* promontory(-ies) a bluff or prominent hill overlooking or projecting into a lowland */
    final public const PT    = 'PT';    /* point a tapering piece of land projecting into a body of water, less prominent than a cape */
    final public const PTS   = 'PTS';   /* points tapering pieces of land projecting into a body of water, less prominent than a cape */
    final public const RDGB  = 'RDGB';  /* beach ridge a ridge of sand just inland and parallel to the beach, usually in series */
    final public const RDGE  = 'RDGE';  /* ridge(s) a long narrow elevation with steep sides, and a more or less continuous crest */
    final public const REG   = 'REG';   /* stony desert a desert plain characterized by a surface veneer of gravel and stones */
    final public const RK    = 'RK';    /* rock a conspicuous, isolated rocky mass */
    final public const RKFL  = 'RKFL';  /* rockfall an irregular mass of fallen rock at the base of a cliff or steep slope */
    final public const RKS   = 'RKS';   /* rocks conspicuous, isolated rocky masses */
    final public const SAND  = 'SAND';  /* sand area a tract of land covered with sand */
    final public const SBED  = 'SBED';  /* dry stream bed a channel formerly containing the water of a stream */
    final public const SCRP  = 'SCRP';  /* escarpment a long line of cliffs or steep slopes separating level surfaces above and below */
    final public const SDL   = 'SDL';   /* saddle a broad, open pass crossing a ridge or between hills or mountains */
    final public const SHOR  = 'SHOR';  /* shore a narrow zone bordering a waterbody which covers and uncovers at high and low water, respectively */
    final public const SINK  = 'SINK';  /* sinkhole a small crater-shape depression in a karst area */
    final public const SLID  = 'SLID';  /* slide a mound of earth material, at the base of a slope and the associated scoured area */
    final public const SLP   = 'SLP';   /* slope(s) a surface with a relatively uniform slope angle */
    final public const SPIT  = 'SPIT';  /* spit a narrow, straight or curved continuation of a beach into a waterbody */
    final public const SPUR  = 'SPUR';  /* spur(s) a subordinate ridge projecting outward from a hill, mountain or other elevation */
    final public const TAL   = 'TAL';   /* talus slope a steep concave slope formed by an accumulation of loose rock fragments at the base of a cliff or steep slope */
    final public const TRGD  = 'TRGD';  /* interdune trough(s) a long wind-swept trough between parallel longitudinal dunes */
    final public const TRR   = 'TRR';   /* terrace a long, narrow alluvial platform bounded by steeper slopes above and below, usually overlooking a waterbody */
    final public const UPLD  = 'UPLD';  /* upland an extensive interior region of high land with low to moderate surface relief */
    final public const VAL   = 'VAL';   /* valley an elongated depression usually traversed by a stream */
    final public const VALG  = 'VALG';  /* hanging valley a valley the floor of which is notably higher than the valley or shore to which it leads; most common in areas that have been glaciated */
    final public const VALS  = 'VALS';  /* valleys elongated depressions usually traversed by a stream */
    final public const VALX  = 'VALX';  /* section of valley */
    final public const VLC   = 'VLC';   /* volcano a conical elevation composed of volcanic materials with a crater at the top */

    /* U codes → undersea, etc. */
    final public const APNU  = 'APNU';  /* apron a gentle slope, with a generally smooth surface, particularly found around groups of islands and seamounts */
    final public const ARCU  = 'ARCU';  /* arch a low bulge around the southeastern end of the island of Hawaii */
    final public const ARRU  = 'ARRU';  /* arrugado an area of subdued corrugations off Baja California */
    final public const BDLU  = 'BDLU';  /* borderland a region adjacent to a continent, normally occupied by or bordering a shelf, that is highly irregular with depths well in excess of those typical of a shelf */
    final public const BKSU  = 'BKSU';  /* banks elevations, typically located on a shelf, over which the depth of water is relatively shallow but sufficient for safe surface navigation */
    final public const BNKU  = 'BNKU';  /* bank an elevation, typically located on a shelf, over which the depth of water is relatively shallow but sufficient for safe surface navigation */
    final public const BSNU  = 'BSNU';  /* basin a depression more or less equidimensional in plan and of variable extent */
    final public const CDAU  = 'CDAU';  /* cordillera an entire mountain system including the subordinate ranges, interior plateaus, and basins */
    final public const CNSU  = 'CNSU';  /* canyons relatively narrow, deep depressions with steep sides, the bottom of which generally has a continuous slope */
    final public const CNYU  = 'CNYU';  /* canyon a relatively narrow, deep depression with steep sides, the bottom of which generally has a continuous slope */
    final public const CRSU  = 'CRSU';  /* continental rise a gentle slope rising from oceanic depths towards the foot of a continental slope */
    final public const DEPU  = 'DEPU';  /* deep a localized deep area within the confines of a larger feature, such as a trough, basin or trench */
    final public const EDGU  = 'EDGU';  /* shelf edge a line along which there is a marked increase of slope at the outer margin of a continental shelf or island shelf */
    final public const ESCU  = 'ESCU';  /* escarpment (or scarp) an elongated and comparatively steep slope separating flat or gently sloping areas */
    final public const FANU  = 'FANU';  /* fan a relatively smooth feature normally sloping away from the lower termination of a canyon or canyon system */
    final public const FLTU  = 'FLTU';  /* flat a small level or nearly level area */
    final public const FRZU  = 'FRZU';  /* fracture zone an extensive linear zone of irregular topography of the sea floor, characterized by steep-sided or asymmetrical ridges, troughs, or escarpments */
    final public const FURU  = 'FURU';  /* furrow a closed, linear, narrow, shallow depression */
    final public const GAPU  = 'GAPU';  /* gap a narrow break in a ridge or rise */
    final public const GLYU  = 'GLYU';  /* gully a small valley-like feature */
    final public const HLLU  = 'HLLU';  /* hill an elevation rising generally less than 500 meters */
    final public const HLSU  = 'HLSU';  /* hills elevations rising generally less than 500 meters */
    final public const HOLU  = 'HOLU';  /* hole a small depression of the sea floor */
    final public const KNLU  = 'KNLU';  /* knoll an elevation rising generally more than 500 meters and less than 1,000 meters and of limited extent across the summit */
    final public const KNSU  = 'KNSU';  /* knolls elevations rising generally more than 500 meters and less than 1,000 meters and of limited extent across the summits */
    final public const LDGU  = 'LDGU';  /* ledge a rocky projection or outcrop, commonly linear and near shore */
    final public const LEVU  = 'LEVU';  /* levee an embankment bordering a canyon, valley, or seachannel */
    final public const MESU  = 'MESU';  /* mesa an isolated, extensive, flat-topped elevation on the shelf, with relatively steep sides */
    final public const MNDU  = 'MNDU';  /* mound a low, isolated, rounded hill */
    final public const MOTU  = 'MOTU';  /* moat an annular depression that may not be continuous, located at the base of many seamounts, islands, and other isolated elevations */
    final public const MTU   = 'MTU';   /* mountain a well-delineated subdivision of a large and complex positive feature */
    final public const PKSU  = 'PKSU';  /* peaks prominent elevations, part of a larger feature, either pointed or of very limited extent across the summit */
    final public const PKU   = 'PKU';   /* peak a prominent elevation, part of a larger feature, either pointed or of very limited extent across the summit */
    final public const PLNU  = 'PLNU';  /* plain a flat, gently sloping or nearly level region */
    final public const PLTU  = 'PLTU';  /* plateau a comparatively flat-topped feature of considerable extent, dropping off abruptly on one or more sides */
    final public const PNLU  = 'PNLU';  /* pinnacle a high tower or spire-shaped pillar of rock or coral, alone or cresting a summit */
    final public const PRVU  = 'PRVU';  /* province a region identifiable by a group of similar physiographic features whose characteristics are markedly in contrast with surrounding areas */
    final public const RDGU  = 'RDGU';  /* ridge a long narrow elevation with steep sides */
    final public const RDSU  = 'RDSU';  /* ridges long narrow elevations with steep sides */
    final public const RFSU  = 'RFSU';  /* reefs surface-navigation hazards composed of consolidated material */
    final public const RFU   = 'RFU';   /* reef a surface-navigation hazard composed of consolidated material */
    final public const RISU  = 'RISU';  /* rise a broad elevation that rises gently, and generally smoothly, from the sea floor */
    final public const SCNU  = 'SCNU';  /* seachannel a continuously sloping, elongated depression commonly found in fans or plains and customarily bordered by levees on one or two sides */
    final public const SCSU  = 'SCSU';  /* seachannels continuously sloping, elongated depressions commonly found in fans or plains and customarily bordered by levees on one or two sides */
    final public const SDLU  = 'SDLU';  /* saddle a low part, resembling in shape a saddle, in a ridge or between contiguous seamounts */
    final public const SHFU  = 'SHFU';  /* shelf a zone adjacent to a continent (or around an island) that extends from the low water line to a depth at which there is usually a marked increase of slope towards oceanic depths */
    final public const SHLU  = 'SHLU';  /* shoal a surface-navigation hazard composed of unconsolidated material */
    final public const SHSU  = 'SHSU';  /* shoals hazards to surface navigation composed of unconsolidated material */
    final public const SHVU  = 'SHVU';  /* shelf valley a valley on the shelf, generally the shoreward extension of a canyon */
    final public const SILU  = 'SILU';  /* sill the low part of a gap or saddle separating basins */
    final public const SLPU  = 'SLPU';  /* slope the slope seaward from the shelf edge to the beginning of a continental rise or the point where there is a general reduction in slope */
    final public const SMSU  = 'SMSU';  /* seamounts elevations rising generally more than 1,000 meters and of limited extent across the summit */
    final public const SMU   = 'SMU';   /* seamount an elevation rising generally more than 1,000 meters and of limited extent across the summit */
    final public const SPRU  = 'SPRU';  /* spur a subordinate elevation, ridge, or rise projecting outward from a larger feature */
    final public const TERU  = 'TERU';  /* terrace a relatively flat horizontal or gently inclined surface, sometimes long and narrow, which is bounded by a steeper ascending slope on one side and by a steep descending slope on the opposite side */
    final public const TMSU  = 'TMSU';  /* tablemounts (or guyots) seamounts having a comparatively smooth, flat top */
    final public const TMTU  = 'TMTU';  /* tablemount (or guyot) a seamount having a comparatively smooth, flat top */
    final public const TNGU  = 'TNGU';  /* tongue an elongate (tongue-like) extension of a flat sea floor into an adjacent higher feature */
    final public const TRGU  = 'TRGU';  /* trough a long depression of the sea floor characteristically flat bottomed and steep sided, and normally shallower than a trench */
    final public const TRNU  = 'TRNU';  /* trench a long, narrow, characteristically very deep and asymmetrical depression of the sea floor, with relatively steep sides */
    final public const VALU  = 'VALU';  /* valley a relatively shallow, wide depression, the bottom of which usually has a continuous gradient */
    final public const VLSU  = 'VLSU';  /* valleys a relatively shallow, wide depression, the bottom of which usually has a continuous gradient */

    /* V codes → forest, heath, etc. */
    final public const BUSH  = 'BUSH';  /* bush(es) a small clump of conspicuous bushes in an otherwise bare area */
    final public const CULT  = 'CULT';  /* cultivated area an area under cultivation */
    final public const FRST  = 'FRST';  /* forest(s) an area dominated by tree vegetation */
    final public const FRSTF = 'FRSTF'; /* fossilized forest a forest fossilized by geologic processes and now exposed at the earth's surface */
    final public const GROVE = 'GROVE'; /* grove a small wooded area or collection of trees growing closely together, occurring naturally or deliberately planted */
    final public const GRSLD = 'GRSLD'; /* grassland an area dominated by grass vegetation */
    final public const GRVC  = 'GRVC';  /* coconut grove a planting of coconut trees */
    final public const GRVO  = 'GRVO';  /* olive grove a planting of olive trees */
    final public const GRVP  = 'GRVP';  /* palm grove a planting of palm trees */
    final public const GRVPN = 'GRVPN'; /* pine grove a planting of pine trees */
    final public const HTH   = 'HTH';   /* heath an upland moor or sandy area dominated by low shrubby vegetation including heather */
    final public const MDW   = 'MDW';   /* meadow a small, poorly drained area dominated by grassy vegetation */
    final public const OCH   = 'OCH';   /* orchard(s) a planting of fruit or nut trees */
    final public const SCRB  = 'SCRB';  /* scrubland an area of low trees, bushes, and shrubs stunted by some environmental limitation */
    final public const TREE  = 'TREE';  /* tree(s) a conspicuous tree used as a landmark */
    final public const TUND  = 'TUND';  /* tundra a marshy, treeless, high latitude plain, dominated by mosses, lichens, and low shrub vegetation under permafrost conditions */
    final public const VIN   = 'VIN';   /* vineyard a planting of grapevines */
    final public const VINS  = 'VINS';  /* vineyards plantings of grapevines */



    /* A codes → country, state, region, ... */
    final public const A = [
        self::ADM1,
        self::ADM1H,
        self::ADM2,
        self::ADM2H,
        self::ADM3,
        self::ADM3H,
        self::ADM4,
        self::ADM4H,
        self::ADM5,
        self::ADM5H,
        self::ADMD,
        self::ADMDH,
        self::LTER,
        self::PCL,
        self::PCLD,
        self::PCLF,
        self::PCLH,
        self::PCLI,
        self::PCLIX,
        self::PCLS,
        self::PRSH,
        self::TERR,
        self::ZN,
        self::ZNB,
    ];

    /* H codes → Streams, Lakes, etc. */
    final public const H = [
        self::AIRS,
        self::ANCH,
        self::BAY,
        self::BAYS,
        self::BGHT,
        self::BNK,
        self::BNKR,
        self::BNKX,
        self::BOG,
        self::CAPG,
        self::CHN,
        self::CHNL,
        self::CHNM,
        self::CHNN,
        self::CNFL,
        self::CNL,
        self::CNLA,
        self::CNLB,
        self::CNLD,
        self::CNLI,
        self::CNLN,
        self::CNLQ,
        self::CNLSB,
        self::CNLX,
        self::COVE,
        self::CRKT,
        self::CRNT,
        self::CUTF,
        self::DCK,
        self::DCKB,
        self::DOMG,
        self::DPRG,
        self::DTCH,
        self::DTCHD,
        self::DTCHI,
        self::DTCHM,
        self::ESTY,
        self::FISH,
        self::FJD,
        self::FJDS,
        self::FLLS,
        self::FLLSX,
        self::FLTM,
        self::FLTT,
        self::GLCR,
        self::GULF,
        self::GYSR,
        self::HBR,
        self::HBRX,
        self::INLT,
        self::INLTQ,
        self::LBED,
        self::LGN,
        self::LGNS,
        self::LGNX,
        self::LK,
        self::LKC,
        self::LKI,
        self::LKN,
        self::LKNI,
        self::LKO,
        self::LKOI,
        self::LKS,
        self::LKSB,
        self::LKSC,
        self::LKSI,
        self::LKSN,
        self::LKSNI,
        self::LKX,
        self::MFGN,
        self::MGV,
        self::MOOR,
        self::MRSH,
        self::MRSHN,
        self::NRWS,
        self::OCN,
        self::OVF,
        self::PND,
        self::PNDI,
        self::PNDN,
        self::PNDNI,
        self::PNDS,
        self::PNDSF,
        self::PNDSI,
        self::PNDSN,
        self::POOL,
        self::POOLI,
        self::RCH,
        self::RDGG,
        self::RDST,
        self::RF,
        self::RFC,
        self::RFX,
        self::RPDS,
        self::RSV,
        self::RSVI,
        self::RSVT,
        self::RVN,
        self::SBKH,
        self::SD,
        self::SEA,
        self::SHOL,
        self::SILL,
        self::SPNG,
        self::SPNS,
        self::SPNT,
        self::STM,
        self::STMA,
        self::STMB,
        self::STMC,
        self::STMD,
        self::STMH,
        self::STMI,
        self::STMIX,
        self::STMM,
        self::STMQ,
        self::STMS,
        self::STMSB,
        self::STMX,
        self::STRT,
        self::SWMP,
        self::SYSI,
        self::TNLC,
        self::WAD,
        self::WADB,
        self::WADJ,
        self::WADM,
        self::WADS,
        self::WADX,
        self::WHRL,
        self::WLL,
        self::WLLQ,
        self::WLLS,
        self::WTLD,
        self::WTLDI,
        self::WTRC,
        self::WTRH,
    ];

    /* L codes → Parks, Areas, etc. */
    final public const L = [
        self::AGRC,
        self::AMUS,
        self::AREA,
        self::BSND,
        self::BSNP,
        self::BTL,
        self::CLG,
        self::CMN,
        self::CNS,
        self::COLF,
        self::CONT,
        self::CST,
        self::CTRB,
        self::DEVH,
        self::FLD,
        self::FLDI,
        self::GASF,
        self::GRAZ,
        self::GVL,
        self::INDS,
        self::LAND,
        self::LCTY,
        self::MILB,
        self::MNA,
        self::MVA,
        self::NVB,
        self::OAS,
        self::OILF,
        self::PEAT,
        self::PRK,
        self::PRT,
        self::QCKS,
        self::RES,
        self::RESA,
        self::RESF,
        self::RESH,
        self::RESN,
        self::RESP,
        self::RESV,
        self::RESW,
        self::RGN,
        self::RGNE,
        self::RGNH,
        self::RGNL,
        self::RNGA,
        self::SALT,
        self::SNOW,
        self::TRB,
    ];

    /* P codes → city, village, ... */
    final public const P = [
        self::PPL,
        self::PPLA,
        self::PPLA2,
        self::PPLA3,
        self::PPLA4,
        self::PPLA5,
        self::PPLC,
        self::PPLCH,
        self::PPLF,
        self::PPLG,
        self::PPLH,
        self::PPLL,
        self::PPLQ,
        self::PPLR,
        self::PPLS,
        self::PPLW,
        self::PPLX,
        self::STLMT,
    ];

    /* R codes → roads, railroads, etc. */
    final public const R = [
        self::CSWY,
        self::OILP,
        self::PRMN,
        self::PTGE,
        self::RD,
        self::RDA,
        self::RDB,
        self::RDCUT,
        self::RDJCT,
        self::RJCT,
        self::RR,
        self::RRQ,
        self::RTE,
        self::RYD,
        self::ST,
        self::STKR,
        self::TNL,
        self::TNLN,
        self::TNLRD,
        self::TNLRR,
        self::TNLS,
        self::TRL,
    ];

    /* S codes → spots, buildings, farms, etc. */
    final public const S = [
        self::ADMF,
        self::AGRF,
        self::AIRB,
        self::AIRF,
        self::AIRH,
        self::AIRP,
        self::AIRQ,
        self::AIRT,
        self::AMTH,
        self::ANS,
        self::AQC,
        self::ARCH,
        self::ARCHV,
        self::ART,
        self::ASTR,
        self::ASYL,
        self::ATHF,
        self::ATM,
        self::BANK,
        self::BCN,
        self::BDG,
        self::BDGQ,
        self::BLDA,
        self::BLDG,
        self::BLDO,
        self::BP,
        self::BRKS,
        self::BRKW,
        self::BSTN,
        self::BTYD,
        self::BUR,
        self::BUSTN,
        self::BUSTP,
        self::CARN,
        self::CAVE,
        self::CH,
        self::CMP,
        self::CMPL,
        self::CMPLA,
        self::CMPMN,
        self::CMPO,
        self::CMPQ,
        self::CMPRF,
        self::CMTY,
        self::COMC,
        self::CRRL,
        self::CSNO,
        self::CSTL,
        self::CSTM,
        self::CTHSE,
        self::CTRA,
        self::CTRCM,
        self::CTRF,
        self::CTRM,
        self::CTRR,
        self::CTRS,
        self::CVNT,
        self::DAM,
        self::DAMQ,
        self::DAMSB,
        self::DARY,
        self::DCKD,
        self::DCKY,
        self::DIKE,
        self::DIP,
        self::DPOF,
        self::EST,
        self::ESTO,
        self::ESTR,
        self::ESTSG,
        self::ESTT,
        self::ESTX,
        self::FCL,
        self::FNDY,
        self::FRM,
        self::FRMQ,
        self::FRMS,
        self::FRMT,
        self::FT,
        self::FY,
        self::FYT,
        self::GATE,
        self::GDN,
        self::GHAT,
        self::GHSE,
        self::GOSP,
        self::GOVL,
        self::GRVE,
        self::HERM,
        self::HLT,
        self::HMSD,
        self::HSE,
        self::HSEC,
        self::HSP,
        self::HSPC,
        self::HSPD,
        self::HSPL,
        self::HSTS,
        self::HTL,
        self::HUT,
        self::HUTS,
        self::INSM,
        self::ITTR,
        self::JTY,
        self::LDNG,
        self::LEPC,
        self::LIBR,
        self::LNDF,
        self::LOCK,
        self::LTHSE,
        self::MALL,
        self::MAR,
        self::MFG,
        self::MFGB,
        self::MFGC,
        self::MFGCU,
        self::MFGLM,
        self::MFGM,
        self::MFGPH,
        self::MFGQ,
        self::MFGSG,
        self::MKT,
        self::ML,
        self::MLM,
        self::MLO,
        self::MLSG,
        self::MLSGQ,
        self::MLSW,
        self::MLWND,
        self::MLWTR,
        self::MN,
        self::MNAU,
        self::MNC,
        self::MNCR,
        self::MNCU,
        self::MNFE,
        self::MNMT,
        self::MNN,
        self::MNQ,
        self::MNQR,
        self::MOLE,
        self::MSQE,
        self::MSSN,
        self::MSSNQ,
        self::MSTY,
        self::MTRO,
        self::MUS,
        self::NOV,
        self::NSY,
        self::OBPT,
        self::OBS,
        self::OBSR,
        self::OILJ,
        self::OILQ,
        self::OILR,
        self::OILT,
        self::OILW,
        self::OPRA,
        self::PAL,
        self::PGDA,
        self::PIER,
        self::PKLT,
        self::PMPO,
        self::PMPW,
        self::PO,
        self::PP,
        self::PPQ,
        self::PRKGT,
        self::PRKHQ,
        self::PRN,
        self::PRNJ,
        self::PRNQ,
        self::PS,
        self::PSH,
        self::PSN,
        self::PSTB,
        self::PSTC,
        self::PSTP,
        self::PYR,
        self::PYRS,
        self::QUAY,
        self::RDCR,
        self::RDIN,
        self::RECG,
        self::RECR,
        self::REST,
        self::RET,
        self::RHSE,
        self::RKRY,
        self::RLG,
        self::RLGR,
        self::RNCH,
        self::RSD,
        self::RSGNL,
        self::RSRT,
        self::RSTN,
        self::RSTNQ,
        self::RSTP,
        self::RSTPQ,
        self::RUIN,
        self::SCH,
        self::SCHA,
        self::SCHC,
        self::SCHL,
        self::SCHM,
        self::SCHN,
        self::SCHT,
        self::SECP,
        self::SHPF,
        self::SHRN,
        self::SHSE,
        self::SLCE,
        self::SNTR,
        self::SPA,
        self::SPLY,
        self::SQR,
        self::STBL,
        self::STDM,
        self::STNB,
        self::STNC,
        self::STNE,
        self::STNF,
        self::STNI,
        self::STNM,
        self::STNR,
        self::STNS,
        self::STNW,
        self::STPS,
        self::SWT,
        self::SYG,
        self::THTR,
        self::TMB,
        self::TMPL,
        self::TNKD,
        self::TOLL,
        self::TOWR,
        self::TRAM,
        self::TRANT,
        self::TRIG,
        self::TRMO,
        self::TWO,
        self::UNIP,
        self::UNIV,
        self::USGE,
        self::VETF,
        self::WALL,
        self::WALLA,
        self::WEIR,
        self::WHRF,
        self::WRCK,
        self::WTRW,
        self::ZNF,
        self::ZOO,
    ];

    /* T codes → mountains ,hills, rocks, etc. */
    final public const T = [
        self::ASPH,
        self::ATOL,
        self::BAR,
        self::BCH,
        self::BCHS,
        self::BDLD,
        self::BLDR,
        self::BLHL,
        self::BLOW,
        self::BNCH,
        self::BUTE,
        self::CAPE,
        self::CFT,
        self::CLDA,
        self::CLF,
        self::CNYN,
        self::CONE,
        self::CRDR,
        self::CRQ,
        self::CRQS,
        self::CRTR,
        self::CUET,
        self::DLTA,
        self::DPR,
        self::DSRT,
        self::DUNE,
        self::DVD,
        self::ERG,
        self::FAN,
        self::FORD,
        self::FSR,
        self::GAP,
        self::GRGE,
        self::HDLD,
        self::HLL,
        self::HLLS,
        self::HMCK,
        self::HMDA,
        self::INTF,
        self::ISL,
        self::ISLET,
        self::ISLF,
        self::ISLM,
        self::ISLS,
        self::ISLT,
        self::ISLX,
        self::ISTH,
        self::KRST,
        self::LAVA,
        self::LEV,
        self::MESA,
        self::MND,
        self::MRN,
        self::MT,
        self::MTS,
        self::NKM,
        self::NTK,
        self::NTKS,
        self::PAN,
        self::PANS,
        self::PASS,
        self::PEN,
        self::PENX,
        self::PK,
        self::PKS,
        self::PLAT,
        self::PLATX,
        self::PLDR,
        self::PLN,
        self::PLNX,
        self::PROM,
        self::PT,
        self::PTS,
        self::RDGB,
        self::RDGE,
        self::REG,
        self::RK,
        self::RKFL,
        self::RKS,
        self::SAND,
        self::SBED,
        self::SCRP,
        self::SDL,
        self::SHOR,
        self::SINK,
        self::SLID,
        self::SLP,
        self::SPIT,
        self::SPUR,
        self::TAL,
        self::TRGD,
        self::TRR,
        self::UPLD,
        self::VAL,
        self::VALG,
        self::VALS,
        self::VALX,
        self::VLC,
    ];

    /* U codes → undersea, etc. */
    final public const U = [
        self::APNU,
        self::ARCU,
        self::ARRU,
        self::BDLU,
        self::BKSU,
        self::BNKU,
        self::BSNU,
        self::CDAU,
        self::CNSU,
        self::CNYU,
        self::CRSU,
        self::DEPU,
        self::EDGU,
        self::ESCU,
        self::FANU,
        self::FLTU,
        self::FRZU,
        self::FURU,
        self::GAPU,
        self::GLYU,
        self::HLLU,
        self::HLSU,
        self::HOLU,
        self::KNLU,
        self::KNSU,
        self::LDGU,
        self::LEVU,
        self::MESU,
        self::MNDU,
        self::MOTU,
        self::MTU,
        self::PKSU,
        self::PKU,
        self::PLNU,
        self::PLTU,
        self::PNLU,
        self::PRVU,
        self::RDGU,
        self::RDSU,
        self::RFSU,
        self::RFU,
        self::RISU,
        self::SCNU,
        self::SCSU,
        self::SDLU,
        self::SHFU,
        self::SHLU,
        self::SHSU,
        self::SHVU,
        self::SILU,
        self::SLPU,
        self::SMSU,
        self::SMU,
        self::SPRU,
        self::TERU,
        self::TMSU,
        self::TMTU,
        self::TNGU,
        self::TRGU,
        self::TRNU,
        self::VALU,
        self::VLSU,
    ];

    /* V codes → forest, heath, etc. */
    final public const V = [
        self::BUSH,
        self::CULT,
        self::FRST,
        self::FRSTF,
        self::GROVE,
        self::GRSLD,
        self::GRVC,
        self::GRVO,
        self::GRVP,
        self::GRVPN,
        self::HTH,
        self::MDW,
        self::OCH,
        self::SCRB,
        self::TREE,
        self::TUND,
        self::VIN,
        self::VINS,
    ];



    final public const ALL = [
        FeatureClass::A => self::A,
        FeatureClass::H => self::H,
        FeatureClass::L => self::L,
        FeatureClass::P => self::P,
        FeatureClass::R => self::R,
        FeatureClass::S => self::S,
        FeatureClass::T => self::T,
        FeatureClass::U => self::U,
        FeatureClass::V => self::V,
    ];

    private const TEMPLATE_TRANSLATION = '%s.%s';

    /**
     * Returns the translated feature class.
     *
     * @param string $feature
     * @param string|null $locale
     * @return string
     */
    public function translate(string $feature, string $locale = null): string
    {
        $locale ??= $this->locale;

        $featureClass = $this->getFeatureClass($feature);

        return $this->translator->trans(
            sprintf(self::TEMPLATE_TRANSLATION, $featureClass, $feature),
            domain: Domain::FEATURE_CODES,
            locale: $locale,
        );
    }

    /**
     * Returns the feature codes for the given feature class.
     *
     * @param string $featureClass
     * @param string|null $locale
     * @return array<int, array<string, string|int>>
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function getFeatureCodes(
        string $featureClass,
        string $locale = null,
    ): array
    {
        if (!array_key_exists($featureClass, self::ALL)) {
            throw new LogicException(sprintf('Given feature class "%s" does not exist.', $featureClass));
        }

        $featureCodes = [];

        foreach (self::ALL[$featureClass] as $featureCode) {
            $translated = $this->translate($featureCode, $locale);

            $featureCodes[] = [
                KeyArray::CODE => $featureCode,
                KeyArray::TRANSLATED => $translated,
                KeyArray::DISTANCE => 150000,
                KeyArray::LIMIT => 10,
            ];
        }

        uasort($featureCodes, fn($first, $second) => strcmp((string) $first[KeyArray::TRANSLATED], (string) $second[KeyArray::TRANSLATED]));

        return $featureCodes;
    }

    /**
     * Returns the feature codes for all feature classes.
     *
     * @param string|null $locale
     * @return array<string, array<string, array<int, array<string, int|string>>|string>>
     */
    public function getAll(string $locale = null): array
    {
        $locale ??= $this->locale;

        $featureClassService = new FeatureClass($this->translator, $locale);

        $featureClasses = [];

        foreach (FeatureClass::ALL as $featureClass) {
            $featureClasses[$featureClass] = [
                KeyArray::CODE => $featureClass,
                KeyArray::TRANSLATED => $featureClassService->translate($featureClass),
                KeyArray::DATA => $this->getFeatureCodes($featureClass, $locale),
            ];
        }

        return $featureClasses;
    }

    /**
     * Returns the feature codes for the given feature class.
     *
     * @param string $featureClass
     * @param string|null $locale
     * @param string|null $filter
     * @return array<int, array{
     *     id: string,
     *     name: string,
     *     relevance: int,
     * }>
     */
    public function getFeatureCodesAutoCompletion(
        string $featureClass,
        string $locale = null,
        string $filter = null
    ): array
    {
        if (!array_key_exists($featureClass, self::ALL)) {
            throw new LogicException(sprintf('Given feature class "%s" does not exist.', $featureClass));
        }

        $featureCodes = [];

        foreach (self::ALL[$featureClass] as $featureCode) {
            $translated = $this->translate($featureCode, $locale);

            if (
                !is_null($filter) &&
                !str_contains(strtolower($translated), strtolower($filter)) &&
                !str_contains(strtolower($featureCode), strtolower($filter))
            ) {
                continue;
            }

            $featureCodes[] = [
                KeyArray::ID => $featureCode,
                KeyArray::NAME => $translated,
                KeyArray::RELEVANCE => 0,
            ];
        }

        uasort($featureCodes, fn($first, $second) => strcmp((string) $first[KeyArray::NAME], (string) $second[KeyArray::NAME]));

        return $featureCodes;
    }

    /**
     * Returns all feature codes filtered for auto-completion.
     *
     * @param string $queryString
     * @param string|null $locale
     * @return array<int, array{
     *     id: string,
     *     name: string,
     *     relevance: int,
     * }>
     */
    public function getAllAutoCompletion(string $queryString, string $locale = null): array
    {
        $locale ??= $this->locale;

        $featureClasses = [];

        foreach (FeatureClass::ALL as $featureClass) {
            $featureClasses = [
                ...$featureClasses,
                ...$this->getFeatureCodesAutoCompletion(
                    featureClass: $featureClass,
                    locale: $locale,
                    filter: $queryString
                ),
            ];
        }

        return $featureClasses;
    }

    /**
     * Returns the feature class
     *
     * @param string $featureCode
     * @return string
     */
    public function getFeatureClass(string $featureCode): string
    {
        foreach (self::ALL as $featureClass => $featureCodes) {
            if (in_array($featureCode, $featureCodes, true)) {
                return (string) $featureClass;
            }
        }

        throw new LogicException(sprintf('Given feature code "%s" is not a valid feature code.', $featureCode));
    }
}
