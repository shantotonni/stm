CREATE TABLE [dbo].[UserManager](
    [UserID] [nvarchar](50) PRIMARY KEY NOT NULL,
    [UserType] [nvarchar](255) NOT NULL,
    [Password] [nvarchar](255) NOT NULL,
    [Status] [char](1) NULL,
    [LoginFalseAttempt] [INT] DEFAULT 0,
    [LoginActiveTime] [DATETIME ] NULL ,
    [CreatedAt] [datetime] NULL,
    [UpdatedAt] [datetime] NULL,
    [CreatedBy] [nvarchar](150) NULL,
    [UpdatedBy] [nvarchar](150) NULL
    )

CREATE TABLE [dbo].[UserLog](
    [AppName] [nvarchar](150) NOT NULL,
    [AccessIP] [nvarchar](50) NOT NULL,
    [UserID] [nvarchar](50) NOT NULL,
    [TransactionTime] [datetime] NOT NULL,
    [Browser] [nvarchar](150) NULL,
    [BrowserVersion] [nvarchar](150) NULL,
    [Platform] [nvarchar](150) NULL,
    [Device] [nvarchar](150) NULL,
    [TransactionMessage] [nvarchar](50) NOT NULL,
    )

CREATE TABLE [dbo].[Menu](
    [MenuID] [bigint] PRIMARY KEY IDENTITY(1,1) NOT NULL,
    [AppName] [nvarchar](150) NOT NULL,
    [MenuName] [nvarchar](50) NOT NULL,
    [MenuIcon] [nvarchar](50) NOT NULL,
    [Status] [nvarchar](1) NOT NULL,
    [MenuOrder] [bigint] NULL,
    [HasChild] [char](1) NOT NULL,
    [Link] [nvarchar](150) NULL
    )

CREATE TABLE [dbo].[MenuItem](
    [MenuItemID] [bigint] PRIMARY KEY IDENTITY(1,1) NOT NULL,
    [MenuItemName] [nvarchar](150) NOT NULL,
    [MenuItemIcon] [nvarchar](50) NOT NULL,
    [MenuId] [bigint] NOT NULL,
    [Status] [nvarchar](1) NOT NULL,
    [Link] [nvarchar](50) NOT NULL,
    [MenuItemOrder] [bigint] NULL,
    )

CREATE TABLE [dbo].[UserMenu](
    [UserID] [bigint] NOT NULL,
    [AppName] [nvarchar](150) NOT NULL,
    [MenuType] [nvarchar](50) NOT NULL,
    [RefID] [bigint] NOT NULL
)

CREATE TABLE [dbo].[UserMenu](
    [UserID] [bigint] NOT NULL,
    [AppName] [nvarchar](150) NOT NULL,
    [MenuType] [nvarchar](50) NOT NULL,
    [RefID] [bigint] NOT NULL
    )

CREATE TABLE [dbo].[AppList](
    [AppName] [nvarchar](150) NOT NULL,
    [CreatedDate] [datetime] NULL,
    )




DECLARE @vDate1 AS DATETIME,
	@vDate2 AS DATETIME,
	@vOutlet AS VARCHAR (50),
	@vStoreLocation AS VARCHAR (5),
	@vUserID AS VARCHAR (5)

SET @vDate1='2022-06-02'
SET @vDate2='2020-06-05'
SET @vOutlet='D065'
SET @vStoreLocation = 'L1'
SET @vUserID =''

SELECT m.StockCountID, d.UserID, d.ProductCode ArticleCode,p.BarCode,
       p.ProductName ArticleDescription,pp.UnitPrice Price,
       s.BatchQTY EPSQty, SUM(d.Stock) PhysicalQty, s.BatchQTY-SUM(d.Stock) AS DiffQty
FROM StockCountMaster m
         INNER JOIN  StockCountDetails d ON m.StockCountID=d.StockCountID
         INNER JOIN  Product p ON d.ProductCode=p.ProductCode
         INNER JOIN ProductPrice pp ON pp.ProductCode=p.ProductCode
         INNER JOIN StockBatch s ON s.ProductCode=p.ProductCode
WHERE (d.UserID=@vUserID OR @vUserID='')
  and m.LocationCode=@vStoreLocation
  and m.DepotCode =@vOutlet
  and m.StockCountDate BETWEEN @vDate1 and @vDate1
GROUP BY d.ProductCode,m.StockCountID,d.UserID,p.ProductName,p.BarCode,pp.UnitPrice,s.BatchQTY
ORDER BY m.StockCountID desc

CREATE PROCEDURE SP_StockCountReportSupporting
    @vOutlet varchar(50)=''
AS
SET NOCOUNT ON
SELECT DISTINCT UserID FROM StockCountDetails WHERE (DepotCode = @vOutlet OR @vOutlet='') ORDER BY UserID
SELECT * FROM StoreLocation WHERE Active = 'Y'
SELECT DepotCode,DepotName FROM Depot
Select ProductCode,ProductName from Product
SET NOCOUNT OFF



CREATE PROCEDURE SP_StockCountInventoryDetails
    @vDate1 AS DATETIME,
	@vDate2 AS DATETIME,
	@vOutlet AS VARCHAR (50)='',
	@vStoreLocation AS VARCHAR (15)='',
	@vUserID AS VARCHAR (5)='',
	@vProductCode AS VARCHAR (8000)=''
AS
SET NOCOUNT ON

/*DECLARE @vDate1 AS DATETIME,
	@vDate2 AS DATETIME,
	@vOutlet AS VARCHAR (50),
	@vStoreLocation AS VARCHAR (5),
	@vUserID AS VARCHAR (5),
	@vProductCode AS VARCHAR (8000)

SET @vDate1='2022-06-02'
SET @vDate2='2020-06-12'
SET @vOutlet='D065'
SET @vStoreLocation = 'L1'
SET @vUserID =''
SET @vProductCode=''
*/

-- SP_StockCountInventoryDetails '2022-06-02','2020-06-12'
SELECT m.StockCountID, d.UserID, d.ProductCode ArticleCode,p.BarCode,
       p.ProductName ArticleDescription,pp.UnitPrice Price,
       s.BatchQTY EPSQty, SUM(d.Stock) PhysicalQty, s.BatchQTY-SUM(d.Stock) AS DiffQty
FROM StockCountMaster m
         INNER JOIN  StockCountDetails d ON m.StockCountID=d.StockCountID
         INNER JOIN  Product p ON d.ProductCode=p.ProductCode
         INNER JOIN ProductPrice pp ON (pp.ProductCode=p.ProductCode and pp.DepotCode=m.DepotCode)
         INNER JOIN StockBatch s ON (s.ProductCode=p.ProductCode and s.DepotCode=m.DepotCode)
WHERE (d.UserID=@vUserID OR @vUserID='')
  and (d.LocationCode=@vStoreLocation OR @vStoreLocation='')
  and (m.DepotCode =@vOutlet OR @vOutlet='')
  and m.StockCountDate BETWEEN @vDate1 and @vDate1
GROUP BY d.ProductCode,m.StockCountID,d.UserID,p.ProductName,p.BarCode,pp.UnitPrice,s.BatchQTY
ORDER BY m.StockCountID desc

    SET NOCOUNT OFF

