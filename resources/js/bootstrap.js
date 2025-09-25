window._ = require('lodash');

try {
    require('bootstrap');
} catch (e) {}

window.axios = require('axios');

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

let token = localStorage.getItem('token');

if (token) {
    window.axios.defaults.headers.common['Authorization'] = 'Bearer ' + token;
    // axios.get('/api/check-expired').then((response)=>{
    //     next();
    // }).catch((error)=>{
    //     next({name : 'Login'});
    // })
} else {
    //  console.log('no token');
}

