<template>
    <div class="content">
        <div class="container-fluid">
            <div class="page-title-box">
                <div class="row align-items-center">
                    <div class="col-sm-6">
                        <h4 class="page-title">Users</h4>
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item active">Welcome to Veltrix Dashboard</li>
                        </ol>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-xl-12">
                    <div class="card">
                        <div class="card-body">
                            <data-export/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
// import {bus} from '../../app'
//
// const division = [...new Set(data.map(item => item.Division))];
// let district = [];
// division.forEach((item) => {
//     data.forEach((element) => {
//         if (element.Division === item) {
//             district.push({"Division": item, "District": element.District})
//         }
//     })
// });
//
// district = Object.values(district.reduce((acc,cur)=>Object.assign(acc,{[cur.District]:cur}),{}))
// let upazilla = [];
// district.forEach((item) => {
//     data.forEach((element) => {
//         if (element.District === item.District) {
//             upazilla.push({"Division": item.Division, "District": element.District,"Upazila": element.Upazila})
//         }
//     })
// });
// upazilla = Object.values(upazilla.reduce((acc,cur)=>Object.assign(acc,{[cur.Upazila]:cur}),{}))
//
// let columns = [
//     {title:'Division', key:"Division"},
//     {title:'District', key:"District"},
//     {title:'Upazila', key:"Upazila"}
// ];
// export default{
//     mounted(){
//         bus.$emit(
//             "data-table-import",
//             upazilla,
//             columns,
//             'final'
//         );
//     }
// }
</script>
