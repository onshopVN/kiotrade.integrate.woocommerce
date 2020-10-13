# marketing_onshop.woocommerce

## Event
- in main template : include file javascript : https://gateway.kiotrade.vn/js/script.min.js
  when website be loaded all page, will load this javascript file. Create cookies variable: "af_cid" && "af_id"
- show detail product : send information to api : https://gateway.kiotrade.vn/g1/click
- when create Order   : send all information of Order and products in Order to api : https://gateway.kiotrade.vn/g1/product
- when change status of Order : send all information of Order, include status to api : https://gateway.kiotrade.vn/g1/order
   Mapping status of your system Order to KioTrade Order Status : 
- Example:
-  ===========Your system order status============||=============KioTrade order status============
-    New, In progress, Pendding, Processing       ||                New    ( 1 )
-    Cancel, Return                               ||                Cancel ( 3 ) 
-    Paid                                         ||                Paid   ( 2 ) 
-    Deliveried                                   ||                Finish ( 4 )
   
