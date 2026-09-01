import 'package:dio/dio.dart';
import '../../../core/network/dio_client.dart';

class FieldRepository {
  final Dio dio = DioClient().dio;

  Future<List<dynamic>> getFields({
    String? search,
    String? city,
    String? type,
    double? lat,
    double? lng,
    int? radius,
  }) async {
    try {
      final queryParams = <String, dynamic>{};
      if (search != null && search.isNotEmpty) queryParams['search'] = search;
      if (city != null && city.isNotEmpty) queryParams['city'] = city;
      if (type != null && type.isNotEmpty && type != 'Tous') queryParams['type'] = type;
      if (lat != null && lng != null) {
        queryParams['lat'] = lat;
        queryParams['lng'] = lng;
        if (radius != null) queryParams['radius'] = radius;
      }

      final response = await dio.get('/fields', queryParameters: queryParams);
      if (response.statusCode == 200) {
        return response.data['data'] as List<dynamic>; 
      }
      return [];
    } catch (e) {
      return [];
    }
  }

  Future<List<dynamic>> getOwnerFields() async {
    try {
      final response = await dio.get('/owner/fields');
      if (response.statusCode == 200) {
        return response.data as List<dynamic>; // Laravel get() doesn't paginate by default in this route? Let's assume it's just the array
      }
      return [];
    } catch (e) {
      return [];
    }
  }
}
