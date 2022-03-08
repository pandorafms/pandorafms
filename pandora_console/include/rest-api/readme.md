# Documentación de la api.

path = `http://localhost/pandora_console/ajax.php`

# Los metodos son:

- ## Pedir token:

- **data:**

```json
{
  "page": include / rest - api / index,
  "doLogin": 1,
  "id_user": xxxxxx,
  "password": xxxxx
}
```

- **ejemplo resultado:**

```
ce015de2941dac933621d23d3f32ac5ead8254b7ea3f390494cfcf586d38de27
```

- **ejemplo peticion:**

```
curl "http://localhost/pandora_console/ajax.php?page=include/rest-api/index&doLogin=1&id_user=admin&password=pandora"
```

- ## Traer todos los elementos de una CV.

- **data:**

```javascript
{
	page: include/rest-api/index,
	id_user: XXX,
	getVisualConsoleItems: 1
	visualConsoleId: XX,
	size: [
		widht => XXX,
		height => YYY
	],
	widthScreen: xxx
}
```

- **ejemplo resultado:**

```javascript
[
	{
		"aclGroupId":0,
		"agentDisabled":false,
		"cacheExpiration":0,
		"colorStatus":"#B2B2B2",
		"height":132,
		"id":180,
		"image":"worldmap",
		"imageSrc":"http:\/\/localhost\/pandora_console\/images\/console\/icons\/worldmap.png",
		"isLinkEnabled":true,
		"isOnTop":false,"label":"",
		"labelPosition":"down",
		"link":"http:\/\/localhost\/pandora_console\/index.php?sec=network&sec2=operation%2Fvisual_console%2Fview&id=3&pure=0",
		"linkedLayoutId":3,
		"linkedLayoutNodeId":0,"linkedLayoutStatusType":"default","moduleDisabled":false,
		"parentId":0,
		"type":5,
		"width":200,
		"x":1675,
		"y":184
	},
	{
		"aclGroupId":0,
		"agentDisabled":false,
		"cacheExpiration":0,
		"colorStatus":"#B2B2B2","height":132,"id":181,"image":"europemap","imageSrc":"http:\/\/localhost\/pandora_console\/images\/console\/icons\/europemap.png",
		"isLinkEnabled":true,
		"isOnTop":false,
		"label":"",
		"labelPosition":"down",
		"link":"http:\/\/localhost\/pandora_console\/index.php?sec=network&sec2=operation%2Fvisual_console%2Fview&id=4&pure=0",
		"linkedLayoutId":4,
		"linkedLayoutNodeId":0,"linkedLayoutStatusType":"default","moduleDisabled":false,
		"parentId":0,
		"type":5,
		"width":200,
		"x":1673,
		"y":340
	}
	...
]
```

- **ejemplo peticion:**

```
curl "http://localhost/pandora_console/ajax.php?page=include/rest-api/index&getVisualConsoleItems=1&auth_hash=ce015de2941dac933621d23d3f32ac5ead8254b7ea3f390494cfcf586d38de27&visualConsoleId=7&id_user=admin"
```

- ## Traer los datos del propio item.

- **data:**

```javascript
{
page: include/rest-api/index,
,
getVisualConsoleItem: 1,
visualConsoleId: XX,
visualConsoleItemId: XX
}
```

- **ejemplo resultado:**

```javascript
{
	"aclGroupId":0,
	"agentDisabled":false,
	"cacheExpiration":0,
	"clockFormat":"time",
	"clockTimezone":"Europe\/Madrid",
	"clockTimezoneOffset":3600,
	"clockType":"digital",
	"color":"#FFFFFF",
	"colorStatus":"#B2B2B2",
	"height":50,
	"id":212,
	"isLinkEnabled":true,
	"isOnTop":false,
	"label":"",
	"labelPosition":"down",
	"link":null,
	"linkedLayoutId":0,
	"linkedLayoutNodeId":0,
	"linkedLayoutStatusType":"default",
	"moduleDisabled":false,
	"parentId":0,
	"showClockTimezone":true,
	"type":19,
	"width":100,
	"x":848
	"y":941
}
```

- **ejemplo peticion:**

```
curl "http://localhost/pandora_console/ajax.php?page=include/rest-api/index&getVisualConsoleItem=1&auth_hash=ce015de2941dac933621d23d3f32ac5ead8254b7ea3f390494cfcf586d38de27&visualConsoleId=7&visualConsoleItemId=212&id_user=admin"
```
