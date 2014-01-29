<?php
/**
 * Created by PhpStorm.
 * User: tsraveling
 * Date: 1/20/14
 * Time: 12:00 AM
 */

require("bootstrap.php");

function lout($tx)
{
    echo "<div class='logtext'>$tx</div>";
}

$projectName="Invalid";
$projectCopyright="None";
$today = date("F j, Y");

$fileOut = null;

function fout($tx)
{
    global $fileOut;
    $tx = str_replace("§","\n",$tx);
    fwrite($fileOut,$tx);
}

function makeFileHeader($fname)
{
    global $projectName,$today,$projectCopyright;
    return "//§".
        "//  $fname §".
        "//  $projectName §".
        "//  §".
        "//  Created by Timothy Raveling on $today.§".
        "//  Copyright (c) $projectCopyright. All rights reserved.§".
        "//  §";
}

start_header("Compiler");
end_header();

$parentObject = 0;
if (isset($_GET["object"]))
    $parentObject = $_GET["object"];

if (isset($_GET["id"])) {

    $stmt = $DBH->prepare("SELECT * FROM projects WHERE id=:uid");
    $stmt->bindParam(":uid",$_GET["id"],PDO::PARAM_INT);
    $stmt->execute();

    if ($project = $stmt->fetch()) {

        $projectName = $project->title;
        $projectCopyright = $project->copyright;
        $dirPath = "/Users/tsraveling/Documents/DataBuilder/".str_replace(" ","_",$project->title)."/";

        $title = "Compiling $projectName";
        if ($parentObject>0) {
            $title .= ": ".objectNameForId($parentObject);
        }
        startBlock($title);
        lout("Building to: " . $dirPath);

        // Make the folder if it doesn't exist
        if (!file_exists($dirPath)) {
            mkdir($dirPath, 0777, true);
        } else {
            lout("Emptying the folder");
            $files = glob($dirPath."*.*"); // get all file names
            foreach($files as $file){ // iterate files
                if(is_file($file))
                    unlink($file); // delete file
            }
        }

        endBlock();

        if ($parentObject!=0) {
            $ostmt = $DBH->prepare("SELECT * FROM objects WHERE project=:pid AND id=:parid");
            $ostmt->bindParam(":pid",$project->id,PDO::PARAM_INT);
            $ostmt->bindParam(":parid",$parentObject,PDO::PARAM_INT);
            $ostmt->execute();
        } else {
            $ostmt = $DBH->prepare("SELECT * FROM objects WHERE project=:pid AND parent=0");
            $ostmt->bindParam(":pid",$project->id,PDO::PARAM_INT);
            $ostmt->execute();
        }

        while ($object = $ostmt->fetch()) {
            recursiveCompile($object);
        }

        // Fill the DataManager, if we're compiling the whole project
        if ($parentObject==0) {
            startBlock("DataManager insert");

            $fname = "DataManagerInsert.h";
            $fpath = $dirPath.$fname;
            $fileOut = fopen($fpath,"w");
            lout("Building $fname");

            fout("§// Copy-in JSON");
            fout("§+ (<#Class Name#>)fromJSON:(id)json;§");
            fout("- (NSMutableDictionary*)toJSON;§");

            fout("§- (id)init;§");
            fout("§// Coding§");
            fout("- (void)saveSelf;§");
            fout("- (void)encodeWithCoder:(NSCoder *)encoder;§");
            fout("- (id)initWithCoder:(NSCoder *)decoder;§");

            fclose($fileOut);

            $fname = "DataManagerInsert.m";
            $fpath = $dirPath.$fname;
            $fileOut = fopen($fpath,"w");

            lout("Building $fname");

            fout("§// Copy-in JSON");
            fout("§+ (<#Class Name#>)fromJSON:(id)json§");
            fout("{§    return NULL;§}§");
            fout("§- (NSMutableDictionary*)toJSON§");
            fout("{§    return NULL;§}§");

            fout("§// Data init§");
            // Data init
            fout("-(id)init§");
            fout("{§");
            fout("    if (self)§");
            fout("    {§");
            fout("        NSArray *paths = NSSearchPathForDirectoriesInDomains(NSCachesDirectory, NSUserDomainMask, YES);§");
            fout("        NSString *documentsDirectory = [paths objectAtIndex:0];§");
            fout("        NSString *path=[documentsDirectory stringByAppendingPathComponent:@\"data.dat\"];§§");
            fout("        NSData *data = [[NSMutableData alloc] initWithContentsOfFile:path];§");
            fout("        NSKeyedUnarchiver *unarchiver = [[NSKeyedUnarchiver alloc] initForReadingWithData:data];§§");
            fout("        DataManager *temp = [unarchiver decodeObjectForKey:@\"Data\"];§");
            fout("        if (temp)§");
            fout("            self = temp;§");
            fout("        else {§");
            fout("            NSLog(@\"Data.dat not found, initializing\");§");
            fout("            //TODO: Custom initialization goes here§");
            fout("        }§§");
            fout("        [unarchiver finishDecoding];§");
            fout("    }§");
            fout("    return self;§");
            fout("}§");


            // Self-saving for DataManager
            fout("§#pragma mark - Saving§");
            fout("§- (void)saveSelf§");
            fout("{§");
            fout("    NSArray *paths = NSSearchPathForDirectoriesInDomains(NSCachesDirectory, NSUserDomainMask, YES);§");
            fout("    NSString *documentsDirectory = [paths objectAtIndex:0];§");
            fout("    NSString *path=[documentsDirectory stringByAppendingPathComponent:@\"data.dat\"];§§");
            fout("    NSMutableData *data = [[NSMutableData alloc] init];§");
            fout("    NSKeyedArchiver *archiver = [[NSKeyedArchiver alloc] initForWritingWithMutableData:data];§§");
            fout("    [archiver encodeObject:self forKey:@\"Data\"];§");
            fout("    [archiver finishEncoding];§§");
            fout("    [data writeToFile:path atomically:YES];§");
            fout("}§");

            // External coding
            fout("§#pragma mark - Coding§");

            fout("§- (void)encodeWithCoder:(NSCoder *)encoder§{§");
            fout("    // TODO: encode data§");
            fout("}§");

            fout("§- (id)initWithCoder:(NSCoder *)decoder§{§");
            fout("    if (self = [super init]) {§");
            fout("        // TODO: decode data§");
            fout("    }§");
            fout("    return self;§");
            fout("}§");

            fclose($fileOut);

            lout("Done!");

            endBlock();
        }

        startBlock("Complete!");
        if (isset($_GET["object"]))
            doLink("Back to object","object.php?id=".$_GET["object"]);
        else
            doLink("Back to project","project.php?id=".$_GET["id"]);
        endBlock();

    } else {
        echo "Invalid.";
    }


} else {
    echo "Invalid.";
}

function recursiveCompile($res)
{
    global $DBH;

    // Compile the object
    compileObject($res);

    $stmt=$DBH->prepare("SELECT * FROM objects WHERE parent=:uid ORDER BY title");
    $stmt->bindParam(":uid",$res->id,PDO::PARAM_INT);
    $stmt->execute();
    if ($stmt->rowCount()>0) {
        while ($sres = $stmt->fetch()) {
            recursiveCompile($sres);
        }
    }
}

function compileObject($object) {
    global $DBH,$fileOut,$dirPath;

    $dfInsert = "@\"yyyy-MM-dd\"";

    $classname = makeClassName($object->title);

    startBlock($object->title);
    lout("Class Name: ".$classname);

    ///////// BUILD .H FILE //////////

    $fname = $classname.".h";
    $fpath = $dirPath.$fname;
    $fileOut = fopen($fpath,"w");
    lout("Opened $fname for writing.");

    // Add header
    fout(makeFileHeader($fname));
    if (strlen($object->comments)>0)
        fout("//  ".$object->comments."§//  §");

    // Add interface and variables

    $vardecs = "";
    $varprops = "";
    $varsynth = "";

    $varimports = "";

    $varencoder = "";
    $vardecoder = "";

    $varinstancedec = "";
    $varinstanceencode = "";
    $varinit = "";

    $vargetset = "";
    $vargetsetdec = "";

    $varadders = "";
    $varaddersdec = "";

    $vardefines = "";
    $vardefineoutputs = "";
    $vardefineoutputdecs = "";

    $varjsondecode = "";
    $varjsonencode = "";

    $var_stmt = $DBH->prepare("SELECT * FROM variables WHERE parent=".$object->id." ORDER BY kind,title");
    $var_stmt->execute();
    while ($variable = $var_stmt->fetch())
    {
        // Get the variable name
        $varname = makeVarName($variable->title,$variable->kind);
        $varhandle = str_replace(" ","",$variable->title);
        $lowvar = strtolower($varhandle);

        $idhandle = "x";
        if ($variable->kind==7) {
            $idhandle = makeIDHandle($variable->title);
            if ($variable->title == "UID")
                $idhandle = "iid";
        }

        // Get the subclass type
        $varc = variableClassType($variable->kind);
        if ($varc=="x")$varc=$variable->class;

        $shorthand_varc = shorthandClassType($variable->kind);
        if ($shorthand_varc=="x*")$shorthand_varc = $variable->class."*";

        // Get includes
        if (($variable->kind==4 || $variable->kind==5) && $variable->class!="") {

            $importstring = "#import \"".$variable->class.".h\"§";

            if ($variable->class=="UIImage")$importstring = "";
            if ($variable->class=="NSString")$importstring = "";
            if ($variable->class=="NSNumber")$importstring = "";
            if ($variable->class=="NSDate")$importstring = "";
            if ($importstring != "") {
                if (!strpos($varimports,$importstring)===false)$importstring = "";
            }

            if ($importstring != "")
                $varimports .= $importstring;
        }

        // Build declarations
        $vardecs .= "    $varc *$varname;";
        if (strlen($variable->comments)>0)$vardecs.=" //".$variable->comments;
        $vardecs.="§";

        // Build property declares
        $varprops .= "@property (nonatomic,retain) $varc *$varname;§";

        // Build synthesizers
        if ($varsynth != "")
            $varsynth.=", ";
        else $varsynth = "@synthesize ";
        $varsynth .= $varname;

        // Build extra ID declaration for IDs
        if ($variable->kind==7) {
            $vardecs .= "    int $idhandle; // ID handle for $varname §";
            $varprops .= "@property (assign) int $idhandle;§";
            $varsynth .= ", $idhandle";
        }

        // Build Encoder
        $varencoder .= "    [encoder encodeObject:$varname forKey:@\"$varname\"];§";
        $vardecoder .= "        if ([decoder containsValueForKey:@\"$varname\"]) $varname = [decoder decodeObjectForKey:@\"$varname\"];§";

        // Build instance
        if ($variable->kind != 4 && $variable->in_instance == 1) { // Arrays and UIDs don't get included in the instance function

            // Build instance declaration
            if ($varinstancedec=="")
                $varinstancedec = "+ ($classname*)instanceWith";
            else
                $varinstancedec .= " with";

            if ($variable->kind == 7)
                $varinstancedec .= $varhandle . ":(int)$lowvar";
            else
                $varinstancedec .= $varhandle . ":($shorthand_varc)$lowvar";

            // Build instance code
            if ($variable->kind==0)$varinstanceencode .= "    ret.$varname = [NSNumber numberWithInt:$lowvar];§";
            if ($variable->kind==1)$varinstanceencode .= "    ret.$varname = [NSNumber numberWithFloat:$lowvar];§";
            if ($variable->kind==6)$varinstanceencode .= "    ret.$varname = [NSNumber numberWithBool:$lowvar];§";
            if ($variable->kind>1 && $variable->kind<6)
                $varinstanceencode .= "    ret.$varname = $lowvar;§";

            if ($variable->kind==7) {
                $varinstanceencode .= "    if ($lowvar != -1) ret.$varname = [NSString stringWithFormat:@\"const%i\",$lowvar];§";
                $varinstanceencode .= "    ret.$idhandle = $lowvar;§";
            }
        }

        // Build init
        if ($variable->kind == 4) {
            $varinit .= "        $varname = [[NSMutableArray alloc] init];§";
        }

        if ($variable->defaultval != "" && $variable->kind != 4 && $variable->kind != 5) {
            if ($variable->kind == 0) $varinit .= "        $varname = [NSNumber numberWithInt:".$variable->defaultval."];§";
            if ($variable->kind == 1) $varinit .= "        $varname = [NSNumber numberWithFloat:".$variable->defaultval."];§";
            if ($variable->kind == 2) $varinit .= "        $varname = @\"".$variable->defaultval."\";§";
            if ($variable->kind == 3) {
                if ($variable->defaultval == "now")$varinit .= "        $varname = [NSDate date];§";
            }
            if ($variable->kind == 6) $varinit .= "        $varname = [NSNumber numberWithBOOL:".$variable->defaultval."];§";
        }

        if ($variable->kind == 7 && $variable->title == "UID") {
            $varinit .= "§        // Create unique id§";
            $varinit .= "        CFUUIDRef uuidRef = CFUUIDCreate(NULL);§";
            $varinit .= "        CFStringRef uuidStringRef = CFUUIDCreateString(NULL, uuidRef);§";
            $varinit .= "        CFRelease(uuidRef);§";
            $varinit .= "        $varname = (__bridge NSString *)uuidStringRef;§";
        }

        // Adders
        if ($variable->kind == 4) {
            $addname = $variable->class;
            if (substr($addname,0,3)=="Dat")
                $addname = substr($addname,3);

            $varaddersdec .= "- (void)add$addname:(".$variable->class."*)ob;§";

            $varadders .= "§- (void)add$addname:(".$variable->class."*)ob§";
            $varadders .= "{§";
            $varadders .= "    [$varname addObject:ob];§";
            $varadders .= "}§";

            // Getter based on UID

            $varaddersdec .= "// - (".$variable->class."*)get$addname"."ForID:(int)i;§";

            $varadders .= "§// Get from array by id§";
            $varadders .= "/*§";
            $varadders .= "- (".$variable->class."*)get$addname"."ForID:(int)i§";
            $varadders .= "{§";
            $varadders .= "    for (".$variable->class." *ob in $varname) {§";
            $varadders .= "        if ([ob hasID:[NSString stringWithFormat:@\"const%i\",i]])§";
            $varadders .= "            return ob;§";
            $varadders .= "    }§";
            $varadders .= "    return nil;§";
            $varadders .= "}§ */§";
        }

        // Getters and Setters
        if ($variable->kind == 0) { // Ints

            $vargetsetdec .= "- (void)set$varhandle:(int)val;§";
            $vargetsetdec .= "- (int)get$varhandle;§";

            $vargetset .= "§- (void)set$varhandle:(int)val§";
            $vargetset .= "{§";
            $vargetset .= "    $varname = [NSNumber numberWithInt:val];§";
            $vargetset .= "}§";

            $vargetset .= "§- (int)get$varhandle §";
            $vargetset .= "{§";
            $vargetset .= "    return [$varname intValue];§";
            $vargetset .= "}§";

            if ($variable->defines != "") {
                $vargetsetdec .= "- (BOOL)is$varhandle:(int)k;§";

                $vargetset .= "§- (BOOL)is$varhandle:(int)k§";
                $vargetset .= "{§";
                $vargetset .= "    if ([$varname intValue]==k)return TRUE;§";
                $vargetset .= "    return FALSE;§";
                $vargetset .= "}§";
            }
        }

        if ($variable->kind == 1) { // Floats
            $vargetsetdec .= "- (void)set$varhandle:(float)val;§";
            $vargetsetdec .= "- (float)get$varhandle;§";

            $vargetset .= "§- (void)set$varhandle:(float)val§";
            $vargetset .= "{§";
            $vargetset .= "    $varname = [NSNumber numberWithFloat:val];§";
            $vargetset .= "}§";

            $vargetset .= "§- (float)get$varhandle §";
            $vargetset .= "{§";
            $vargetset .= "    return [$varname floatValue];§";
            $vargetset .= "}§";
        }

        if ($variable->kind == 6) { // Bools
            $vargetsetdec .= "- (void)set$varhandle:(BOOL)val;§";
            $vargetsetdec .= "- (BOOL)is$varhandle;§";

            $vargetset .= "§- (void)set$varhandle:(BOOL)val§";
            $vargetset .= "{§";
            $vargetset .= "    $varname = [NSNumber numberWithBool:val];§";
            $vargetset .= "}§";

            $vargetset .= "§- (BOOL)is$varhandle §";
            $vargetset .= "{§";
            $vargetset .= "    return [$varname boolValue];§";
            $vargetset .= "}§";
        }

        if ($variable->kind == 7 && $variable->title == "UID") { // ID
            $vargetsetdec .= "- (BOOL)hasID:(NSString*)uid;§";

            $vargetset .= "§- (BOOL)hasID:(NSString*)uid§";
            $vargetset .= "{§";
            $vargetset .= "    return [uid isEqualToString:sUID]; §";
            $vargetset .= "}§";
        }

        // Defines
        if ($variable->kind==0 && $variable->defines!="") {

            $vardefineoutputdecs .= "+ (NSString*)get$varhandle"."Label:(int)i§;§";
            $vardefineoutputdecs .= "- (NSString*)get$varhandle"."Label;§";

            $vardefineoutputs .= "§+ (NSString*)get$varhandle"."Label:(int)i§";
            $vardefineoutputs .= "{§";
            $vardefineoutputs .= "    switch(i) {§";

            $vardefines .= "§// ".$variable->title." defines§";
            $defar = explode(", ",$variable->defines);
            foreach ($defar as $defchunk) {
                $chunkar = explode(" ",$defchunk);
                $def = "#define k$varhandle".$chunkar[1];
                $makeup = 40-strlen($def);
                for ($i=0;$i<$makeup;$i++) { $def.=" "; }
                $def .= $chunkar[0];
                $vardefines .= $def."§";

                $vardefineoutputs .= "        case ".$chunkar[0].":§";
                $vardefineoutputs .= "            return @\"".$chunkar[1]."\";§";
                $vardefineoutputs .= "            break;§";
            }

            $vardefineoutputs .= "        default:break;§";
            $vardefineoutputs .= "    }§";
            $vardefineoutputs .= "    return @\"Unkown\";§";
            $vardefineoutputs .= "}§";
            $vardefineoutputs .= "§- (NSString*)get$varhandle"."Label§";
            $vardefineoutputs .= "{§";
            $vardefineoutputs .= "    return [$classname get$varhandle"."Label:[self get$varhandle]];§";
            $vardefineoutputs .= "}§";
        }

        // JSON decoder (from dictionary)
        $jsonkey = $variable->jsonkey;
        $varjsondecode .= "    if ([json objectForKey:@\"$jsonkey\"]) {§";
        if ($variable->kind==0 || $variable->kind==1 || $variable->kind==6)
            $varjsondecode .= "        ob.$varname = (NSNumber*)[json objectForKey:@\"$jsonkey\"];§";
        if ($variable->kind==2 || $variable->kind==7)
            $varjsondecode .= "        ob.$varname = [json objectForKey:@\"$jsonkey\"];§";
        if ($variable->kind==3) {
            $formatter = "df_".strtolower($varhandle);
            $varjsondecode .= "§        // Decode date for $varname §";
            $varjsondecode .= "        NSDateFormatter *$formatter = [[NSDateFormatter alloc] init];§";
            $varjsondecode .= "        [$formatter setDateFormat:$dfInsert];§";
            $varjsondecode .= "        ob.$varname = [$formatter dateFromString:[json objectForKey:@\"$jsonkey\"]];§";
        }
        if ($variable->kind==4) {
            $arname = "ar_".strtolower($varhandle);
            $varjsondecode .= "§        // Fill $varname §";
            $varjsondecode .= "        NSArray *$arname = (NSArray*)[json objectForKey:@\"$jsonkey\"];§";
            $varjsondecode .= "        ob.$varname = [[NSMutableArray alloc] init];§";
            if ($variable->class == "NSString" || $variable->class == "NSNumber" || $variable->class == "NSDate") {
                $varjsondecode .= "        for (".$variable->class." *subob in $arname) {§";
                $varjsondecode .= "            [ob.$varname addObject:subob];§";
                $varjsondecode .= "        }§";
            } else {
                $varjsondecode .= "        for (NSDictionary *subjson in $arname) {§";
                $varjsondecode .= "            ".$variable->class." *subob = [".$variable->class." fromJSON:subjson];§";
                $varjsondecode .= "            [ob.$varname addObject:subob];§";
                $varjsondecode .= "        }§";
            }
        }
        if ($variable->kind==5) {
            $varjsondecode .= "        ob.$varname = [".$variable->class." fromJSON:(NSDictionary*)[json objectForKey:@\"$jsonkey\"]];§";
        }
        $varjsondecode .= "    }§";

        // JSON encoder (encodes to NSMutableDictionary with NSArrays)
        if ($variable->kind<=2 || $variable->kind==6 || $variable->kind==7)
            $varjsonencode .= "    [dict setValue:$varname forKey:@\"$jsonkey\"];§";
        if ($variable->kind==3) {
            $formatter = "df_".strtolower($varhandle);
            $varjsonencode .= "§    // Encode date for $varname §";
            $varjsonencode .= "    NSDateFormatter *$formatter = [[NSDateFormatter alloc] init];§";
            $varjsonencode .= "    [$formatter setDateFormat:$dfInsert];§";
            $varjsonencode .= "    [dict setValue:[$formatter stringFromDate:$varname] forKey:@\"$jsonkey\"];§";
        }
        if ($variable->kind==4) {
            $arname = "ar_".strtolower($varhandle);
            $varjsonencode .= "§    // Encode $varname §";
            $varjsonencode .= "    NSMutableArray *$arname = [[NSMutableArray alloc] init];§";
            $varjsonencode .= "    for (".$variable->class." *subob in $varname) {§";
            if ($variable->class == "NSString" || $variable->class == "NSNumber" || $variable->class == "NSDate") {
                $varjsonencode .= "        [$arname addObject:subob];§";
            } else {
                $varjsonencode .= "        [$arname addObject:[subob toJSON]];§";
            }
            $varjsonencode .= "    }§";
            $varjsonencode .= "    [dict setValue:[NSArray arrayWithArray:$arname] forKey:@\"$jsonkey\"];§";
        }
        if ($variable->kind==5) {
            $varjsonencode .= "    [dict setValue:[$varname toJSON] forKey:@\"$jsonkey\"];§";
        }
    }

    // Add includes
    fout("§§#import <Foundation/Foundation.h>§");
    fout($varimports);
    /* Old import code
    $so_stmt = $DBH->prepare("SELECT * FROM objects WHERE parent=:pid");
    $so_stmt->bindParam(":pid",$object->id,PDO::PARAM_INT);
    $so_stmt->execute();
    while ($subobject = $so_stmt->fetch()) {
        fout( "#import \"" . makeClassName($subobject->title) . ".h\"§");
    } */

    fout($vardefines);

    fout("§§@interface $classname : NSObject§{§");
    fout($vardecs);
    fout("}§§");
    fout($varprops);

    // Instance and init
    fout("§// Base functions§");
    fout("- (id)init;§");
    fout("+ ($classname*)instance;§");
    fout($varinstancedec.";§");
    fout("+ ($classname*)fromJSON:(id)json;§");
    fout("- (NSDictionary*)toJSON;§");

    // Declare getters and setters
    if ($vargetsetdec != "") {
        fout("§// Getters and Setters§");
        fout($vargetsetdec);
        fout($varaddersdec);
    }

    if ($vardefineoutputs != "") {
        fout("§// Indexed values§");
        fout($vardefineoutputdecs);
    }

    // Declare coders
    fout("§// Coders§");
    fout("- (void)encodeWithCoder:(NSCoder *)encoder;§");
    fout("- (id)initWithCoder:(NSCoder *)decoder;§");

    // Wrap it up
    fout("§@end");

    // Close the file
    fclose($fileOut);


    ///////// BUILD .M FILE //////////

    $fname = $classname.".m";
    $fpath = $dirPath.$fname;
    $fileOut = fopen($fpath,"w");
    lout("Opened $fname for writing.");

    fout(makeFileHeader($fname));
    if (strlen($object->comments)>0)
        fout("//  ".$object->comments."§//  §");

    // Grab the .h file
    fout("§#import \"$classname.h\"§");

    // Kick it off
    fout("§@implementation $classname §");

    // Synthesize
    fout("$varsynth;§");

    // Adders
    if ($varadders!="") {
        fout("§#pragma mark - Adders§");
        fout($varadders);
    }

    // Init
    fout("§#pragma mark - Base Functions§");
    fout("§- (id)init§");
    fout("{§");
    fout("    if (self) {§");
    fout($varinit);
    fout("    }§");
    fout("    return self;§");
    fout("}§");

    // Basic Instance
    fout("§+ ($classname*)instance§");
    fout("{§");
    fout("    $classname* ret = [[$classname alloc] init];§");
    fout("    return ret;§");
    fout("}§");

    // Advanced instance
    fout("§".$varinstancedec."§");
    fout("{§");
    fout("    $classname* ret = [$classname instance];§");
    fout($varinstanceencode);
    fout("    return ret;§");
    fout("}§");

    // Getters and setters

    if ($vargetset!="") {
        fout("§#pragma mark - Getters and Setters§");
        fout($vargetset);
    }

    // Indexed values

    if ($vardefineoutputs != "") {
        fout("§#pragma mark - Indexed values§");
        fout($vardefineoutputs);
    }

    // Build from JSON
    fout("§#pragma mark - JSON§");
    fout("§+ ($classname*)fromJSON:(id)json§");
    fout("{§");
    fout("    $classname *ob = [$classname instance];§");
    fout($varjsondecode);
    fout("    return ob;§");
    fout("}§");

    // Build to JSON (NSDictionary)
    fout("§- (NSDictionary*)toJSON§");
    fout("{§");
    fout("    NSMutableDictionary *dict = [[NSMutableDictionary alloc] init];§");
    fout($varjsonencode);
    fout("    return [NSDictionary dictionaryWithDictionary:dict];§");
    fout("}§");

    // Build decoder
    fout("§#pragma mark - Coding§");
    fout("§- (void)encodeWithCoder:(NSCoder *)encoder {§");
    fout($varencoder);
    fout("}§");

    // Build encoder
    fout("§- (id)initWithCoder:(NSCoder *)decoder {§");
    fout("    if (self = [super init]) { §");
    fout($vardecoder);
    fout("    }§");
    fout("    return self;§");
    fout("}§");

    // Wrap it up
    fout("§@end");

    fclose($fileOut);

    endBlock();
}

do_footer();

?>