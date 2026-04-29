function search(nameKey, myArray){
    let foundResult=new Array();
    let counter = 0;
    myArray = Array.isArray(myArray) ? myArray : [];
    nameKey = nameKey == null ? "" : String(nameKey);
    for (let i=0; i < myArray.length; i++) {
        // if (myArray[i].item_name === nameKey) {
        //     return myArray[i];
        // }
        let itemName = myArray[i].item_name == null ? "" : String(myArray[i].item_name);
        let itemCode = myArray[i].item_code == null ? "" : String(myArray[i].item_code);
        let categoryName = myArray[i].category_name == null ? "" : String(myArray[i].category_name);
        let vegItem = myArray[i].veg_item == null ? "" : String(myArray[i].veg_item);
        let beverageItem = myArray[i].beverage_item == null ? "" : String(myArray[i].beverage_item);
        let normalizedNameKey = nameKey.toLowerCase();
        if (itemName.toLowerCase().includes(normalizedNameKey) || itemCode.toLowerCase().includes(normalizedNameKey) || categoryName.toLowerCase().includes(normalizedNameKey) || vegItem.includes(nameKey) || beverageItem.toUpperCase().includes(nameKey)) {
            foundResult.push(myArray[i]);
            counter++;
            if (nameKey && counter == 12) {
                break;
            }
        }
    }
    return foundResult.sort( function(a, b) {
      return parseInt(b.sold_for)-parseInt(a.sold_for);
    });
    //this is comment. it could be used if we want to sort this collection of object by item_name or anything else
    // return foundResult.sort( predicateBy("item_name") );
    
}
function getAlternativeNameById(menu_id,myArray){
    let name = '';
    myArray = Array.isArray(myArray) ? myArray : [];
    for (let i=0; i < myArray.length; i++) {
        if (Number(myArray[i].item_id) === Number(menu_id)) {
            if(myArray[i].alternative_name){
                name = "("+myArray[i].alternative_name+")";
            }
        }
    }
    return name;
}

function searchAddress(nameKey, myArray){
    let foundResult=new Array();
    let counter = 0;
    myArray = Array.isArray(myArray) ? myArray : [];
    for (let i=0; i < myArray.length; i++) {
        // if (myArray[i].item_name === nameKey) {
        //     return myArray[i];
        // }
        if (myArray[i].customer_id == nameKey) {
            foundResult.push(myArray[i]);
            counter++;
            if (nameKey && counter == 12) {
                break;
            }
        }
    }
    return foundResult;
    
}

function search_by_menu_id(menu_id,myArray){
    let foundResult=new Array();
    myArray = Array.isArray(myArray) ? myArray : [];
    for (let i=0; i < myArray.length; i++) {
        if (Number(myArray[i].item_id) ===  Number(menu_id)) {
            foundResult.push(myArray[i]);
        }
    }
    return foundResult.sort();
}
function search_by_menu_id_getting_parent_id(menu_id,myArray){
    let parent_id = '';
    myArray = Array.isArray(myArray) ? myArray : [];
    for (let i=0; i < myArray.length; i++) {
        if (Number(myArray[i].item_id) === Number(menu_id)) {
            parent_id = myArray[i].parent_id;
        }
    }
    return parent_id;
}
function get_variations_search_by_menu_id(menu_id,myArray){
    let foundResult=new Array();
    myArray = Array.isArray(myArray) ? myArray : [];
    for (let i=0; i < myArray.length; i++) {
        if (Number(myArray[i].parent_id) === menu_id) {
            foundResult.push(myArray[i]);
        }
    }
    return foundResult.sort();
}

function predicateBy(prop){
   return function(a,b){
      if( a[prop] > b[prop]){
          return 1;
      }else if( a[prop] < b[prop] ){
          return -1;
      }
      return 0;
   }
}
